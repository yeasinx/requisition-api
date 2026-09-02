<?php

namespace App\Policies;

use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
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

        $settings = $this->settingsService->getSettings();

        return match ($requisition->current_step) {
            RequisitionStep::APPROVER_1 => $user->id === $settings->first_approver_user_id,
            RequisitionStep::APPROVER_2 => $user->id === $settings->second_approver_user_id,
            RequisitionStep::BUSINESS_CONTROLLER => $user->id === $settings->business_controller_user_id,
            RequisitionStep::ACCOUNTS => $user->id === $settings->accounts_approver_user_id,
            RequisitionStep::HR_ADMIN => $user->id === $settings->hr_admin_approver_user_id,
            default => false,
        };
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

        $settings = $this->settingsService->getSettings();

        // Check if user is the designated approver for the current step
        return match ($requisition->current_step) {
            RequisitionStep::APPROVER_1 => $user->id === $settings->first_approver_user_id,
            RequisitionStep::APPROVER_2 => $user->id === $settings->second_approver_user_id,
            RequisitionStep::BUSINESS_CONTROLLER => $user->id === $settings->business_controller_user_id,
            RequisitionStep::ACCOUNTS => $user->id === $settings->accounts_approver_user_id,
            RequisitionStep::HR_ADMIN => $user->id === $settings->hr_admin_approver_user_id,
            default => false,
        };
    }

    /**
     * Determine if the user can deny a requisition.
     * Same rules as approved.
     */
    public function deny(User $user, Requisition $requisition): bool
    {
        return $this->approve($user, $requisition);
    }
}
