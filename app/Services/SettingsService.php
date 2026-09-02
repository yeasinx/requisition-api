<?php

namespace App\Services;

use App\Models\SystemSettings;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function getSettings(): SystemSettings
    {
        return Cache::remember('system_settings', 3600, function () {
            return SystemSettings::first() ?? SystemSettings::create([
                'updated_by_user_id' => 1,
            ]);
        });
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
}
