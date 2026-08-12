<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EmploymentStatus;
use App\Models\User;

class EmploymentStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin);
    }

    public function view(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function update(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function delete(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }
}
