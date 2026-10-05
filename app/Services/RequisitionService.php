<?php

namespace App\Services;

use App\Enums\RequisitionStatus;
use App\Mail\RequisitionCcMail;
use App\Mail\RequisitionPendingApprovalMail;
use App\Models\Requisition;
use App\Models\RequisitionAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mail;
use RuntimeException;
use Throwable;

class RequisitionService
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected RequisitionNumberService $requisitionNumberService,
        protected SettingsService $settingsService
    ) {}

    /**
     * Prepare line items with calculated total prices and sum overall total.
     *
     * @param  array<int, array{item_name: string, description: string, quantity: int, unit_price: float|int}>  $rawItems
     * @return array{0: array<int, array>, 1: float}
     */
    public function processItems(array $rawItems): array
    {
        $items = array_map(function (array $item) {
            $item['total_price'] = round($item['quantity'] * $item['unit_price'], 2);

            return $item;
        }, $rawItems);

        $totalPrice = round(array_sum(array_column($items, 'total_price')), 2);

        return [$items, $totalPrice];
    }

    /**
     * Create a new requisition with items inside a database transaction.
     */
    public function create(User $user, array $data): Requisition
    {
        [$items, $totalPrice] = $this->processItems($data['items']);
        $initialStep = $this->workflowService->getInitialStep($user);
        $initialApprover = $this->settingsService->getApproverForStep($initialStep);

        $requisition = DB::transaction(function () use ($user, $items, $totalPrice, $initialStep, $data) {
            $requisition = Requisition::create([
                'requisition_number' => $this->requisitionNumberService->generate(),
                'submitted_by_user_id' => $user->id,
                'current_step' => $initialStep,
                'status' => RequisitionStatus::PENDING,
                'total_expected_price' => $totalPrice,
            ]);

            $requisition->items()->createMany($items);

            $requisition->ccContacts()->sync($data['cc_contact_ids'] ?? []);

            $this->addAttachments($requisition, $user, $data['attachments'] ?? []);

            return $requisition->load(['submittedBy', 'items', 'attachments.uploadedBy', 'ccContacts']);
        });

        if ($initialApprover?->email) {
            Mail::to($initialApprover->email)->queue(
                new RequisitionPendingApprovalMail($requisition, $initialApprover)
            );
        }

        $this->workflowService->notifyCcContacts($requisition, RequisitionCcMail::SUBMITTED);

        return $requisition;
    }

    /**
     * Update an existing requisition's items and total price inside a database transaction.
     */
    public function update(Requisition $requisition, array $data): Requisition
    {
        [$items, $totalPrice] = $this->processItems($data['items']);

        return DB::transaction(function () use ($requisition, $items, $totalPrice) {
            $requisition->items()->delete();
            $requisition->items()->createMany($items);

            $requisition->update([
                'total_expected_price' => $totalPrice,
            ]);

            return $requisition->load(['submittedBy', 'items', 'attachments.uploadedBy', 'ccContacts']);
        });
    }

    /**
     * Store uploaded files on the private disk and record them against the requisition.
     * Stored files are removed again if any step fails, so no orphans are left behind.
     *
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, RequisitionAttachment>
     */
    public function addAttachments(Requisition $requisition, User $user, array $files): Collection
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($requisition, $user, $files, &$storedPaths) {
                $attachments = new Collection;

                foreach ($files as $file) {
                    $path = $file->store("requisitions/{$requisition->id}", RequisitionAttachment::DISK);

                    if ($path === false) {
                        throw new RuntimeException('Failed to store attachment.');
                    }

                    $storedPaths[] = $path;

                    $attachments->push($requisition->attachments()->create([
                        'uploaded_by_user_id' => $user->id,
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                        'size' => $file->getSize(),
                    ]));
                }

                return $attachments->load('uploadedBy');
            });
        } catch (Throwable $e) {
            Storage::disk(RequisitionAttachment::DISK)->delete($storedPaths);

            throw $e;
        }
    }
}
