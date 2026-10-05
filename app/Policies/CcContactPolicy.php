<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\CcContact;
use App\Models\User;

class CcContactPolicy
{
    /**
     * Any authenticated user can list contacts (to pick CC recipients when submitting).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CcContact $ccContact): bool
    {
        return true;
    }

    /**
     * Only SUPER_ADMIN manages the contact directory.
     */
    public function create(User $user): bool
    {
        return $user->role === UserType::SUPER_ADMIN;
    }

    public function update(User $user, CcContact $ccContact): bool
    {
        return $user->role === UserType::SUPER_ADMIN;
    }

    public function delete(User $user, CcContact $ccContact): bool
    {
        return $user->role === UserType::SUPER_ADMIN;
    }
}
