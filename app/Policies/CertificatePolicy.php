<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }
}
