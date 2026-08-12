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

    public function resetTwoFactor(User $user, User $target): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    /**
     * Impersonasi:
     * - operator yayasan (admin yayasan/ketua) → semua pengguna aktif
     * - operator satker → hanya GTK pada satuan kerja yang sama.
     */
    public function impersonate(User $user, User $target): bool
    {
        if ($user->id === $target->id || ! $target->is_active) {
            return false;
        }

        if ($user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin)) {
            return true;
        }

        return $user->hasRole(UserRole::UnitAdmin)
            && $target->role === UserRole::Employee
            && $target->work_unit_id === $user->work_unit_id;
    }
}
