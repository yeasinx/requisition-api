<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequisitionAttachmentRequest;
use App\Http\Resources\RequisitionAttachmentResource;
use App\Models\Requisition;
use App\Models\RequisitionAttachment;
use App\Services\RequisitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequisitionAttachmentController extends Controller
{
    public function __construct(protected RequisitionService $requisitionService) {}

    /**
     * Upload one or more attachments to an existing requisition.
     */
    public function store(StoreRequisitionAttachmentRequest $request, Requisition $requisition): JsonResponse
    {
        Gate::authorize('update', $requisition);

        $files = $request->file('attachments');

        if ($requisition->attachments()->count() + count($files) > RequisitionAttachment::MAX_PER_REQUISITION) {
            throw ValidationException::withMessages([
                'attachments' => 'A requisition can have at most '.RequisitionAttachment::MAX_PER_REQUISITION.' attachments.',
            ]);
        }

        $attachments = $this->requisitionService->addAttachments($requisition, $request->user(), $files);

        return RequisitionAttachmentResource::collection($attachments)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Download an attachment's file.
     */
    public function download(Requisition $requisition, RequisitionAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $requisition);

        $disk = Storage::disk(RequisitionAttachment::DISK);

        abort_unless($disk->exists($attachment->path), 404, 'Attachment file not found.');

        return $disk->download($attachment->path, $attachment->original_name);
    }

    /**
     * Remove an attachment and its file.
     */
    public function destroy(Requisition $requisition, RequisitionAttachment $attachment): JsonResponse
    {
        Gate::authorize('update', $requisition);

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully',
        ]);
    }
}
