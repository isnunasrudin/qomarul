<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Decree;
use App\Models\User;

class DecreePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin)
            || $user->employee_id !== null;
    }

    public function view(User $user, Decree $decree): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin)
            || $user->employee_id === $decree->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function update(User $user, Decree $decree): bool
    {
        return $decree->status->value === 'draft'
            && $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function delete(User $user, Decree $decree): bool
    {
        return $decree->status->value === 'draft'
            && $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function submit(User $user, Decree $decree): bool
    {
        return $decree->status->value === 'draft'
            && $user->hasRole(UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function verify(User $user, Decree $decree): bool
    {
        return $decree->status->value === 'submitted'
            && $user->hasRole(UserRole::FoundationAdmin);
    }

    public function reject(User $user, Decree $decree): bool
    {
        return in_array($decree->status->value, ['submitted', 'verified'], true)
            && $user->hasRole(UserRole::FoundationAdmin, UserRole::FoundationHead);
    }

    public function sign(User $user, Decree $decree): bool
    {
        return $decree->status->value === 'verified'
            && $user->hasRole(UserRole::FoundationHead);
    }

    public function cancel(User $user, Decree $decree): bool
    {
        return $decree->status->value === 'issued'
            && $user->hasRole(UserRole::FoundationHead);
    }
}
