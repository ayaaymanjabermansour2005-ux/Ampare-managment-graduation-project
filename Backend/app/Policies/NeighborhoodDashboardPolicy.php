<?php

namespace App\Policies;

use App\Models\User;

class NeighborhoodDashboardPolicy
{
    public function view(User $user): bool
    {
        return $user->can('neighborhoods.dashboard') && $user->isAdmin();
    }
}
