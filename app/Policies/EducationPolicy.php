<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Education;
use App\Models\User;

class EducationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function view(User $user, Education $education): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin)
            || $user->employee_id === $education->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function update(User $user, Education $education): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin)
            || $user->employee_id === $education->employee_id;
    }

    public function delete(User $user, Education $education): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin)
            || $user->employee_id === $education->employee_id;
    }
}
