<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AdditionalDuty;
use App\Models\User;

class AdditionalDutyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin);
    }

    public function view(User $user, AdditionalDuty $additionalDuty): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function update(User $user, AdditionalDuty $additionalDuty): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function delete(User $user, AdditionalDuty $additionalDuty): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }
}
