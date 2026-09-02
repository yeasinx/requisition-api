<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\SystemSettings;
use App\Models\User;

class SystemSettingsPolicy
{
    /**
     * Determine whether the user can view system settings.
     */
    public function view(User $user): bool
    {
        return $user->role === UserType::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can update system settings.
     */
    public function update(User $user): bool
    {
        return $user->role === UserType::SUPER_ADMIN;
    }
}
