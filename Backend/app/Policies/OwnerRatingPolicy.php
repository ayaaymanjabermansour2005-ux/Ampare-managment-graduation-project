<?php

namespace App\Policies;

use App\Models\User;

class OwnerRatingPolicy
{
    public function viewAny(User $user, User $owner): bool
    {
        return $user->isAdmin() || $user->id === $owner->id;
    }
}
