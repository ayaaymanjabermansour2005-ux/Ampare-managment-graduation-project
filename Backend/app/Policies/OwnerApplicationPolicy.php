<?php

namespace App\Policies;

use App\Models\OwnerApplication;
use App\Models\User;

class OwnerApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.create');
    }

    public function view(User $user, OwnerApplication $application): bool
    {
        return $user->can('users.create');
    }

    public function review(User $user, OwnerApplication $application): bool
    {
        return $user->can('users.create') && $application->isPending();
    }

    public function updateInternalNote(User $user, OwnerApplication $application): bool
    {
        return $user->can('users.create');
    }
}
