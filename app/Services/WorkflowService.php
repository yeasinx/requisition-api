<?php

namespace App\Services;

use App\Enums\DecisionStatus;
use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Mail\RequisitionApprovedMail;
use App\Mail\RequisitionDeniedMail;
use App\Mail\RequisitionPendingApprovalMail;
use App\Models\ApprovalStep;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class WorkflowService
{
    public function __construct(protected SettingsService $settingsService) {}

    public function getInitialStep(User $submitter): RequisitionStep
    {
        $ceo = $this->settingsService->getSecondApprover();
        $pm = $this->settingsService->getFirstApprover();

        // 1. If the submitter is the CEO, skip App1 and App2, start at Business Controller
        if ($ceo && $submitter->is($ceo)) {
            return RequisitionStep::BUSINESS_CONTROLLER;
        }

        // 2. If the submitter is the PM, skip App1, start at App2
        if ($pm && $submitter->is($pm) ||
            $submitter->role === UserType::ACCOUNTS ||
            $submitter->role === UserType::HR_ADMIN
        ) {
            return RequisitionStep::APPROVER_2;
        }

        // 3. Default to regular Employees: Start at PM (App1)
        return RequisitionStep::APPROVER_1;
    }

    public function getNextStep(RequisitionStep $currentStep): ?RequisitionStep
    {
        $sequence = [
            RequisitionStep::APPROVER_1,
            RequisitionStep::APPROVER_2,
            RequisitionStep::BUSINESS_CONTROLLER,
            RequisitionStep::ACCOUNTS,
            RequisitionStep::HR_ADMIN,
        ];

        $currentIndex = array_search($currentStep, $sequence);

        if ($currentIndex === false || $currentIndex === count($sequence) - 1) {
            return null;
        }

        return $sequence[$currentIndex + 1];
    }

    /**
     * Record approval audit step and advance the workflow.
     */
    public function approve(Requisition $requisition, User $user, ?string $remarks = null): Requisition
    {
        $submitter = $requisition->submittedBy;
        $nextStep = $this->getNextStep($requisition->current_step);

        $requisition = DB::transaction(function () use ($requisition, $user, $remarks, $nextStep) {
            ApprovalStep::create([
                'requisition_id' => $requisition->id,
                'step_type' => $requisition->current_step,
                'acted_by_user_id' => $user->id,
                'decision' => DecisionStatus::APPROVED,
                'remarks' => $remarks,
                'acted_at' => now(),
            ]);

            if ($nextStep === null) {
                $requisition->update([
                    'status' => RequisitionStatus::APPROVED,
                    'current_step' => null,
                ]);
            } else {
                $requisition->update([
                    'current_step' => $nextStep,
                ]);
            }

            return $requisition->load(['submittedBy', 'items', 'approvals.actedBy']);
        });

        if ($submitter?->email) {
            Mail::to($submitter->email)->queue(
                new RequisitionApprovedMail(
                    $requisition,
                    $user,
                    $remarks,
                    isFullyApproved: $nextStep === null
                )
            );
        }

        if ($nextStep !== null) {
            $nextApprover = $this->settingsService->getApproverForStep($nextStep);
            if ($nextApprover?->email) {
                Mail::to($nextApprover->email)->queue(
                    new RequisitionPendingApprovalMail($requisition, $nextApprover)
                );
            }
        }

        return $requisition;
    }

    /**
     * Record denial audit step and halt the workflow.
     */
    public function deny(Requisition $requisition, User $user, ?string $remarks = null): Requisition
    {
        if (empty(trim((string) $remarks))) {
            throw new InvalidArgumentException('A reason must be provided when denying a requisition.');
        }

        $submitter = $requisition->submittedBy;
        $requisition = DB::transaction(function () use ($requisition, $user, $remarks) {
            ApprovalStep::create([
                'requisition_id' => $requisition->id,
                'step_type' => $requisition->current_step,
                'acted_by_user_id' => $user->id,
                'decision' => DecisionStatus::DENIED,
                'remarks' => $remarks,
                'acted_at' => now(),
            ]);

            $requisition->update([
                'status' => RequisitionStatus::DENIED,
                'current_step' => null,
            ]);

            return $requisition->load(['submittedBy', 'items', 'approvals.actedBy']);
        });

        if ($submitter?->email) {
            Mail::to($submitter->email)->queue(
                new RequisitionDeniedMail($requisition, $user, $remarks)
            );
        }

        return $requisition;
    }
}
