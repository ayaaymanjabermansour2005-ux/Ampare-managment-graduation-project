<?php

namespace App\Policies;

use App\Enums\FaultStatus;
use App\Models\Fault;
use App\Models\User;

class FaultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('faults.view')
            && ($user->isAdmin() || $user->isOwner() || $user->isSubscriber() || $user->isTechnician());
    }

    public function view(User $user, Fault $fault): bool
    {
        if (! $user->can('faults.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $user->id === $fault->generator?->owner_id;
        }

        if ($user->isSubscriber()) {
            return $fault->generator?->subscriptions()
                ->whereHas('subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $user->id))
                ->exists() ?? false;
        }

        if ($user->isTechnician()) {
            return $fault->generator
                ?->technicians()
                ->where('technicians.user_id', $user->id)
                ->exists() ?? false;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('faults.create')
            && ($user->isSubscriber() || $user->isOwner() || $user->isAdmin() || $user->isTechnician());
    }

    public function verify(User $user, Fault $fault): bool
    {
        if (! $user->can('faults.updateStatus')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isOwner() || $user->id !== $fault->generator?->owner_id) {
            return false;
        }

        return $fault->status === FaultStatus::PendingVerification;
    }

    public function decideRepair(User $user, Fault $fault): bool
    {
        if (! $user->can('faults.updateStatus')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isOwner() || $user->id !== $fault->generator?->owner_id) {
            return false;
        }

        return $fault->status === FaultStatus::Verified;
    }

    public function overrideStatus(User $user, Fault $fault): bool
    {
        return $user->isAdmin() && $user->can('faults.override_status');
    }

    public function delete(User $user, Fault $fault): bool
    {
        return $user->can('faults.delete') && $user->isAdmin();
    }
}