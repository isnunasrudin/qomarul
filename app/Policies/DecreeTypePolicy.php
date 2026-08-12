<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DecreeType;
use App\Models\User;

class DecreeTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin);
    }

    public function view(User $user, DecreeType $decreeType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function update(User $user, DecreeType $decreeType): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }

    public function delete(User $user, DecreeType $decreeType): bool
    {
        return $user->hasRole(UserRole::FoundationAdmin);
    }
}
