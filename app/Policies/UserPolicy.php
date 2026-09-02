<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserType::SUPER_ADMIN, UserType::HR_ADMIN], true);
    }

    /**
     * Determine whether the user can view the specific user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || in_array($user->role, [UserType::SUPER_ADMIN, UserType::HR_ADMIN], true);
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserType::SUPER_ADMIN, UserType::HR_ADMIN], true);
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        return in_array($user->role, [UserType::SUPER_ADMIN, UserType::HR_ADMIN], true);
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete self, only SUPER_ADMIN can delete users
        return $user->role === UserType::SUPER_ADMIN && $user->id !== $model->id;
    }
}
