<?php

namespace App\Policies;

use App\Models\User;

class AdminDashboardPolicy
{
    public function view(User $user): bool
    {
        return $user->isAdmin();
    }
}
