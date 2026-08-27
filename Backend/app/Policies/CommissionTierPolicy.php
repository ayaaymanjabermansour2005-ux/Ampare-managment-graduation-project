<?php

namespace App\Policies;

use App\Models\CommissionTier;
use App\Models\User;

class CommissionTierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('commission-tiers.manage') && $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->can('commission-tiers.manage') && $user->isAdmin();
    }

    public function update(User $user, CommissionTier $tier): bool
    {
        return $user->can('commission-tiers.manage') && $user->isAdmin();
    }

    public function delete(User $user, CommissionTier $tier): bool
    {
        return $user->can('commission-tiers.manage') && $user->isAdmin();
    }
}
