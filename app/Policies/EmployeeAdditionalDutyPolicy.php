<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EmployeeAdditionalDuty;
use App\Models\User;

class EmployeeAdditionalDutyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin)
            || $user->employee_id !== null;
    }

    public function view(User $user, EmployeeAdditionalDuty $assignment): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin)
            || $user->employee_id === $assignment->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function update(User $user, EmployeeAdditionalDuty $assignment): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function delete(User $user, EmployeeAdditionalDuty $assignment): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }
}
