<?php

namespace App\Policies;

use App\Models\Generator;
use App\Models\User;

class GeneratorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('generators.view')
            && ($user->isAdmin() || $user->isOwner() || $user->isSubscriber() || $user->isTechnician());
    }

    public function view(User $user, Generator $generator): bool
    {
        if (! $user->can('generators.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $generator->owner_id === $user->id;
        }

        if ($user->isSubscriber()) {
            return $generator->subscriptions()
                ->whereHas('subscriberMeter.subscriber', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->exists();
        }

        if ($user->isTechnician()) {
            return $this->isLinkedTechnician($user, $generator);
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->can('generators.create')) {
            return false;
        }

        return $user->isAdmin() || $user->isOwner();
    }

    public function update(User $user, Generator $generator): bool
    {
        if (! $user->can('generators.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $generator->owner_id === $user->id;
    }

    public function delete(User $user, Generator $generator): bool
    {
        if (! $user->can('generators.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $generator->owner_id === $user->id;
    }

    public function manageAttachments(User $user, Generator $generator): bool
    {
        if (! $user->can('generators.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $generator->owner_id === $user->id;
    }

    public function verify(User $user, Generator $generator): bool
    {
        return $user->isAdmin();
    }

    public function record(User $user, Generator $generator): bool
    {
        if (! $user->can('generators.record')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $generator->owner_id === $user->id;
        }

        return $this->isLinkedTechnician($user, $generator);
    }

    private function isLinkedTechnician(User $user, Generator $generator): bool
    {
        if (! $user->isTechnician()) {
            return false;
        }

        return $generator->technicians()
            ->where('technicians.user_id', $user->id)
            ->exists();
    }

    public function manageSchedules(User $user, Generator $generator): bool
    {
        if (! $user->can('generator-schedules.create')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $generator->owner_id === $user->id;
    }

    public function transferOwnership(User $user, Generator $generator): bool
    {
        return $user->can('generators.update') && $user->isAdmin();
    }
}
