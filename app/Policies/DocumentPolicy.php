<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin)
            || $user->employee_id === $document->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::FoundationHead, UserRole::FoundationAdmin, UserRole::UnitAdmin);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }
}
