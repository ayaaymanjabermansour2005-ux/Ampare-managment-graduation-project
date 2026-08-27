<?php

namespace App\Policies;

use App\Models\PlatformCommission;
use App\Models\User;

class PlatformCommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('platform-commissions.view') && ($user->isAdmin() || $user->isOwner());
    }

    public function view(User $user, PlatformCommission $platformCommission): bool
    {
        if (! $user->can('platform-commissions.view')) {
            return false;
        }

        return $user->isAdmin() || $user->id === $platformCommission->owner_id;
    }

    public function updateStatus(User $user, PlatformCommission $platformCommission): bool
    {
        return $user->can('platform-commissions.updateStatus') && $user->isAdmin();
    }
}
