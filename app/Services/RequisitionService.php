<?php

namespace App\Services;

use App\Enums\RequisitionStatus;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequisitionService
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected RequisitionNumberService $requisitionNumberService
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

        return DB::transaction(function () use ($user, $items, $totalPrice, $initialStep) {
            $requisition = Requisition::create([
                'requisition_number' => $this->requisitionNumberService->generate(),
                'submitted_by_user_id' => $user->id,
                'current_step' => $initialStep,
                'status' => RequisitionStatus::PENDING,
                'total_expected_price' => $totalPrice,
            ]);

            $requisition->items()->createMany($items);

            return $requisition->load(['submittedBy', 'items']);
        });
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

            return $requisition->load(['submittedBy', 'items']);
        });
    }
}
