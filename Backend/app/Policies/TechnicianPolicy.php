<?php

namespace App\Policies;

use App\Models\Generator;
use App\Models\Technician;
use App\Models\User;

class TechnicianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('technicians.view') && ($user->isAdmin() || $user->isOwner());
    }

    public function view(User $user, Technician $technician): bool
    {
        if ($user->isAdmin()) {
            return $user->can('technicians.view');
        }

        if ($user->isOwner()) {
            return $user->can('technicians.view') && $technician->owner_id === $user->id;
        }

        return $technician->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('technicians.create') && $user->isOwner();
    }

    public function update(User $user, Technician $technician): bool
    {
        if (! $user->can('technicians.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $technician->owner_id === $user->id;
    }

    public function delete(User $user, Technician $technician): bool
    {
        if (! $user->can('technicians.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $technician->owner_id === $user->id;
    }

    public function manageGeneratorLink(User $user, Technician $technician, Generator $generator): bool
    {
        if (! $user->can('technicians.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner()
            && $technician->owner_id === $user->id
            && $generator->owner_id === $user->id;
    }

    public function manageAttachments(User $user, Technician $technician): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $technician->owner_id === $user->id;
        }

        return $technician->user_id === $user->id;
    }
}
