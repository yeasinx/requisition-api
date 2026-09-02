<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApprovalStepRequest;
use App\Http\Resources\RequisitionResource;
use App\Models\Requisition;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ApprovalStepController extends Controller
{
    public function __construct(protected WorkflowService $workflowService) {}

    /**
     * Approve a requisition at its current step.
     */
    public function approve(StoreApprovalStepRequest $request, Requisition $requisition): JsonResponse
    {
        Gate::authorize('approve', $requisition);

        $validated = $request->validated();

        $requisition = $this->workflowService->approve(
            $requisition,
            $request->user(),
            $validated['remarks'] ?? null
        );

        return new RequisitionResource($requisition)->response();
    }

    /**
     * Deny a requisition at its current step.
     */
    public function deny(StoreApprovalStepRequest $request, Requisition $requisition): JsonResponse
    {
        Gate::authorize('deny', $requisition);

        $validated = $request->validated();

        $requisition = $this->workflowService->deny(
            $requisition,
            $request->user(),
            $validated['remarks'] ?? null
        );

        return new RequisitionResource($requisition)->response();
    }
}
