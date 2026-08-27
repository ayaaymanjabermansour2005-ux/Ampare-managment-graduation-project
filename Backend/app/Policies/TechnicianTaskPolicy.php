<?php

namespace App\Policies;

use App\Models\TechnicianTask;
use App\Models\User;

class TechnicianTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('technician-tasks.view')
            && ($user->isAdmin() || $user->isOwner() || $user->isTechnician());
    }

    public function view(User $user, TechnicianTask $task): bool
    {
        if (! $user->can('technician-tasks.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $user->id === $task->generator?->owner_id;
        }

        if ($user->isTechnician()) {
            return $user->id === $task->technician?->user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('technician-tasks.create') && ($user->isAdmin() || $user->isOwner());
    }

    public function assign(User $user, TechnicianTask $task): bool
    {
        if (! $user->can('technician-tasks.assign') || $task->isClosed()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $task->generator?->owner_id;
    }

    public function onTheWay(User $user, TechnicianTask $task): bool
    {
        if (! $user->can('technician-tasks.start') || ! $user->isTechnician()) {
            return false;
        }

        return $user->id === $task->technician?->user_id && $task->isAssigned();
    }

    public function start(User $user, TechnicianTask $task): bool
    {
        if (! $user->can('technician-tasks.start') || ! $user->isTechnician()) {
            return false;
        }

        return $user->id === $task->technician?->user_id
            && ($task->isAssigned() || $task->isOnTheWay() || $task->isWaitingParts());
    }

    public function markWaitingParts(User $user, TechnicianTask $task): bool
    {
        if (! $user->can('technician-tasks.start') || ! $user->isTechnician()) {
            return false;
        }

        return $user->id === $task->technician?->user_id && $task->isInProgress();
    }

    public function submit(User $user, TechnicianTask $task): bool
    {
        if (! $user->can('technician-tasks.submit') || ! $user->isTechnician()) {
            return false;
        }

        return $user->id === $task->technician?->user_id && $task->isInProgress();
    }

    public function review(User $user, TechnicianTask $task): bool
    {
        if (! $user->can('technician-tasks.review') || ! $task->isSubmitted()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $task->generator?->owner_id;
    }

    public function cancel(User $user, TechnicianTask $task): bool
    {
        if (! $user->can('technician-tasks.cancel') || $task->isClosed() || $task->isSubmitted()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $task->generator?->owner_id;
    }

    public function update(User $user, TechnicianTask $task): bool
    {
        if ($task->isClosed()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $user->id === $task->generator?->owner_id;
        }

        if ($user->isTechnician()) {
            return $user->id === $task->technician?->user_id;
        }

        return false;
    }

    public function rate(User $user, TechnicianTask $task): bool
    {
        if (! $user->can('technician-ratings.create') || ! $task->isApproved() || $task->rating()->exists()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $task->generator?->owner_id;
    }
}
