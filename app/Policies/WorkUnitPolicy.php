<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkUnit;

class WorkUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin);
    }

    public function view(User $user, WorkUnit $workUnit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function update(User $user, WorkUnit $workUnit): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function delete(User $user, WorkUnit $workUnit): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }
}
