<?php

namespace App\Policies;

use App\Enums\RequisitionStatus;
use App\Enums\UserType;
use App\Models\Requisition;
use App\Models\User;
use App\Services\SettingsService;

class RequisitionPolicy
{
    public function __construct(protected SettingsService $settingsService) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Requisition $requisition): bool
    {
        if ($user->role === UserType::SUPER_ADMIN) {
            return true;
        }

        if ($user->id === $requisition->submitted_by_user_id) {
            return true;
        }

        // Anyone who has already acted on the requisition can view it
        if ($requisition->approvals()->where('acted_by_user_id', $user->id)->exists()) {
            return true;
        }

        return $this->isAssignedToCurrentStep($user, $requisition);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role !== UserType::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Requisition $requisition): bool
    {
        if ($user->id !== $requisition->submitted_by_user_id) {
            return false;
        }

        return $requisition->approvals()->count() === 0;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Requisition $requisition): bool
    {
        // Only the submitter can delete
        if ($user->id !== $requisition->submitted_by_user_id) {
            return false;
        }

        // Can only delete it if status is still PENDING
        return $requisition->status === RequisitionStatus::PENDING;
    }

    /**
     * Determine if the user can approve a requisition at its current step.
     * This is the CRITICAL security check!
     */
    public function approve(User $user, Requisition $requisition): bool
    {
        // Requisition must be in PENDING status
        if ($requisition->status !== RequisitionStatus::PENDING) {
            return false;
        }

        // Check if user is the designated approver for the current step
        return $this->isAssignedToCurrentStep($user, $requisition);
    }

    /**
     * Determine if the user can deny a requisition.
     * Same rules as approved.
     */
    public function deny(User $user, Requisition $requisition): bool
    {
        return $this->approve($user, $requisition);
    }

    /**
     * Whether the requisition is waiting at a step the user is assigned to approve.
     */
    private function isAssignedToCurrentStep(User $user, Requisition $requisition): bool
    {
        return $requisition->current_step !== null
            && in_array($requisition->current_step, $this->settingsService->getStepsForUser($user), true);
    }
}
