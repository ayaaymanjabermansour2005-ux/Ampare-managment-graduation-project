<?php

namespace App\Policies;

use App\Models\FaultPrediction;
use App\Models\User;

class FaultPredictionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('fault-predictions.view') && ($user->isAdmin() || $user->isOwner());
    }

    public function view(User $user, FaultPrediction $prediction): bool
    {
        if (! $user->can('fault-predictions.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $prediction->generator?->owner_id;
    }

    public function create(User $user): bool
    {
        return $user->can('fault-predictions.create') && $user->isAdmin();
    }

    public function confirm(User $user, FaultPrediction $prediction): bool
    {
        if (! $user->can('fault-predictions.confirm')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $prediction->generator?->owner_id;
    }

    public function dismiss(User $user, FaultPrediction $prediction): bool
    {
        if (! $user->can('fault-predictions.dismiss')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $prediction->generator?->owner_id;
    }
}
