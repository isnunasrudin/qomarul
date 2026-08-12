<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin);
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin)
            || ($user->hasRole(UserRole::FoundationHead) && $user->id === $target->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin) && $user->id !== $target->id;
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin) && $user->id !== $target->id;
    }
}
