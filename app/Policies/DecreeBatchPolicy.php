<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DecreeBatch;
use App\Models\User;

class DecreeBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function view(User $user, DecreeBatch $batch): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function update(User $user, DecreeBatch $batch): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin)
            && in_array($batch->status->value, ['preparing', 'processing'], true);
    }

    public function delete(User $user, DecreeBatch $batch): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin)
            && in_array($batch->status->value, ['preparing'], true);
    }

    public function sign(User $user, DecreeBatch $batch): bool
    {
        return $user->hasRole(UserRole::FoundationHead)
            && $batch->status->value === 'awaiting_signature';
    }
}
