<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin)
            || $user->employee_id === $employee->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function updateNigy(User $user, Employee $employee): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    /**
     * Membuatkan akun pengguna untuk GTK:
     * operator yayasan → semua GTK; operator satker → GTK satker sendiri.
     */
    public function createUser(User $user, Employee $employee): bool
    {
        if ($employee->user) {
            return false;
        }

        if ($user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin)) {
            return true;
        }

        return $user->hasRole(UserRole::UnitAdmin) && $employee->work_unit_id === $user->work_unit_id;
    }
}
