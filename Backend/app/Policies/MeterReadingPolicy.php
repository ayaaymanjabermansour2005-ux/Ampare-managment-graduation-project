<?php

namespace App\Policies;

use App\Models\MeterReading;
use App\Models\User;

class MeterReadingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('meter-readings.view')
            && ($user->isAdmin() || $user->isOwner() || $user->isSubscriber() || $user->isTechnician());
    }

    public function view(User $user, MeterReading $meterReading): bool
    {
        if (! $user->can('meter-readings.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $subscription = $meterReading->subscription;

        if ($user->isOwner()) {
            return $user->id === $subscription?->generator?->owner_id;
        }

        if ($user->isSubscriber()) {
            return $user->id === $subscription?->subscriberMeter?->subscriber?->user_id;
        }

        if ($user->isTechnician()) {
            return $subscription?->generator
                ?->technicians()
                ->where('technicians.user_id', $user->id)
                ->exists() ?? false;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('meter-readings.create') && ($user->isAdmin() || $user->isOwner() || $user->isTechnician());
    }

    public function approve(User $user, MeterReading $meterReading): bool
    {
        if (! $user->can('meter-readings.approve')) {
            return false;
        }

        if ($meterReading->status->value !== 'pending_approval') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $meterReading->subscription?->generator?->owner_id;
    }

    public function reject(User $user, MeterReading $meterReading): bool
    {
        if (! $user->can('meter-readings.approve')) {
            return false;
        }

        if ($meterReading->status->value !== 'pending_approval') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $meterReading->subscription?->generator?->owner_id;
    }

    public function update(User $user, MeterReading $meterReading): bool
    {
        if (! $user->can('meter-readings.update') || ! $user->isAdmin()) {
            return false;
        }

        return $meterReading->status->value === 'pending_approval';
    }

    public function delete(User $user, MeterReading $meterReading): bool
    {
        if (! $user->can('meter-readings.delete') || ! $user->isAdmin()) {
            return false;
        }

        return $meterReading->status->value === 'pending_approval';
    }

    public function manageAttachments(User $user, MeterReading $meterReading): bool
    {
        if (! $user->can('meter-readings.create')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $subscription = $meterReading->subscription;

        if ($user->isOwner()) {
            return $user->id === $subscription?->generator?->owner_id;
        }

        if ($user->isTechnician()) {
            return $subscription?->generator
                ?->technicians()
                ->where('technicians.user_id', $user->id)
                ->exists() ?? false;
        }

        return false;
    }
}
