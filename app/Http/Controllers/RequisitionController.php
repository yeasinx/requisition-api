<?php

namespace App\Http\Controllers;

use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Http\Requests\StoreRequisitionRequest;
use App\Http\Requests\UpdateRequisitionRequest;
use App\Http\Resources\RequisitionResource;
use App\Models\Requisition;
use App\Services\RequisitionNumberService;
use App\Services\SettingsService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RequisitionController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected RequisitionNumberService $requisitionNumberService,
        protected SettingsService $settingsService
    )
    {}

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
                if (!empty($assignedSteps)) {
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

        // Optional status filtering
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $requisitions = $query->latest()->paginate($request->integer('per_page', 15));

        return RequisitionResource::collection($requisitions)->response();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequisitionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Requisition $requisition)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Requisition $requisition)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequisitionRequest $request, Requisition $requisition)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Requisition $requisition)
    {
        //
    }
}
