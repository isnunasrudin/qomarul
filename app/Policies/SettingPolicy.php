<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function update(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }
}
