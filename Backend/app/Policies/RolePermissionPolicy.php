<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePermissionPolicy
{
    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function sync(User $user, Role $role): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return $role->name !== 'admin';
    }
}
