<?php

namespace App\Services;

use App\Enums\RequisitionStep;
use App\Models\SystemSettings;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function getSettings(): SystemSettings
    {
        $attributes = Cache::remember('system_settings', 3600, function () {
            $settings = SystemSettings::first() ?? SystemSettings::create([
                'updated_by_user_id' => 1,
            ]);

            return $settings->getAttributes();
        });

        // Guard against stale cache entries that stored a serialized object
        // instead of a plain array (e.g. after a class rename or schema change).
        if (! is_array($attributes)) {
            Cache::forget('system_settings');
            $settings = SystemSettings::first() ?? SystemSettings::create([
                'updated_by_user_id' => 1,
            ]);
            $attributes = $settings->getAttributes();
        }

        return new SystemSettings()->newFromBuilder($attributes);
    }

    public function getFirstApprover(): ?User
    {
        return $this->getSettings()->firstApprover;
    }

    public function getSecondApprover(): ?User
    {
        return $this->getSettings()->secondApprover;
    }

    public function getBusinessController(): ?User
    {
        return $this->getSettings()->businessController;
    }

    public function getAccountsApprover(): ?User
    {
        return $this->getSettings()->accountsApprover;
    }

    public function getHrAdminApprover(): ?User
    {
        return $this->getSettings()->hrAdminApprover;
    }

    public function updateSettings(array $data, int $updatedByUserId): SystemSettings
    {
        $settings = $this->getSettings();
        $settings->update(array_merge($data, ['updated_by_user_id' => $updatedByUserId]));

        Cache::forget('system_settings');

        return $settings->fresh();
    }

    public function getApproverForStep(RequisitionStep $step): ?User
    {
        return match ($step) {
            RequisitionStep::APPROVER_1 => $this->getFirstApprover(),
            RequisitionStep::APPROVER_2 => $this->getSecondApprover(),
            RequisitionStep::BUSINESS_CONTROLLER => $this->getBusinessController(),
            RequisitionStep::ACCOUNTS => $this->getAccountsApprover(),
            RequisitionStep::HR_ADMIN => $this->getHrAdminApprover(),
        };
    }

    /**
     * Workflow steps the user is assigned to approve, in workflow order.
     *
     * @return list<RequisitionStep>
     */
    public function getStepsForUser(User $user): array
    {
        $settings = $this->getSettings();

        $assignees = [
            [RequisitionStep::APPROVER_1, $settings->first_approver_user_id],
            [RequisitionStep::APPROVER_2, $settings->second_approver_user_id],
            [RequisitionStep::BUSINESS_CONTROLLER, $settings->business_controller_user_id],
            [RequisitionStep::ACCOUNTS, $settings->accounts_approver_user_id],
            [RequisitionStep::HR_ADMIN, $settings->hr_admin_approver_user_id],
        ];

        $steps = [];
        foreach ($assignees as [$step, $assigneeId]) {
            if ($assigneeId !== null && (int) $assigneeId === $user->id) {
                $steps[] = $step;
            }
        }

        return $steps;
    }
}
