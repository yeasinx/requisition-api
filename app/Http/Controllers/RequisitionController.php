<?php

namespace App\Http\Controllers;

use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Http\Requests\StoreRequisitionRequest;
use App\Http\Requests\UpdateRequisitionRequest;
use App\Http\Resources\RequisitionResource;
use App\Models\Requisition;
use App\Services\RequisitionService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RequisitionController extends Controller
{
    public function __construct(
        protected RequisitionService $requisitionService,
        protected SettingsService $settingsService
    ) {}

    /**
     * List requisitions (filtered based on a user role).
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Requisition::class);

        $user = $request->user();
        $query = Requisition::with(['submittedBy', 'items', 'approvals.actedBy']);

        // Super Admin sees everything
        if ($user->role !== UserType::SUPER_ADMIN) {
            $settings = $this->settingsService->getSettings();

            $assignedSteps = [];
            if ($user->id === $settings->first_approver_user_id) {
                $assignedSteps[] = RequisitionStep::APPROVER_1;
            }
            if ($user->id === $settings->second_approver_user_id) {
                $assignedSteps[] = RequisitionStep::APPROVER_2;
            }
            if ($user->id === $settings->business_controller_user_id) {
                $assignedSteps[] = RequisitionStep::BUSINESS_CONTROLLER;
            }
            if ($user->id === $settings->accounts_approver_user_id) {
                $assignedSteps[] = RequisitionStep::ACCOUNTS;
            }
            if ($user->id === $settings->hr_admin_approver_user_id) {
                $assignedSteps[] = RequisitionStep::HR_ADMIN;
            }

            $query->where(function ($q) use ($user, $assignedSteps) {
                // 1. Always see your own requisitions
                $q->where('submitted_by_user_id', $user->id);

                // 2. See requisitions waiting for current user's approval
                if (! empty($assignedSteps)) {
                    $q->orWhere(function ($subQuery) use ($assignedSteps) {
                        $subQuery->where('status', RequisitionStatus::PENDING)
                            ->whereIn('current_step', $assignedSteps);
                    });
                }

                // 3. See requisitions previously reviewed/acted on by this user
                $q->orWhereHas('approvals', function ($approvalQuery) use ($user) {
                    $approvalQuery->where('acted_by_user_id', $user->id);
                });
            });
        }

        // Status filtering
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $requisitions = $query->latest()->paginate($request->integer('per_page', 15));

        return RequisitionResource::collection($requisitions)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequisitionRequest $request): JsonResponse
    {
        Gate::authorize('create', Requisition::class);

        $requisition = $this->requisitionService->create(
            $request->user(),
            $request->validated()
        );

        return new RequisitionResource($requisition)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified requisition.
     */
    public function show(Requisition $requisition): JsonResponse
    {
        Gate::authorize('view', $requisition);

        $requisition->load(['submittedBy', 'items', 'approvals.actedBy']);

        return new RequisitionResource($requisition)->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequisitionRequest $request, Requisition $requisition): JsonResponse
    {
        Gate::authorize('update', $requisition);

        $requisition = $this->requisitionService->update(
            $requisition,
            $request->validated()
        );

        return new RequisitionResource($requisition)->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Requisition $requisition): JsonResponse
    {
        Gate::authorize('delete', $requisition);

        $requisition->delete();

        return response()->json([
            'message' => 'Requisition deleted successfully',
        ]);
    }
}
