<?php

namespace App\Policies;

use App\Models\Subscriber;
use App\Models\User;

class SubscriberPolicy
{
    public function updateBeneficiaryType(User $user, Subscriber $subscriber): bool
    {
        if (! $user->can('subscribers.updateBeneficiaryType') || ! $user->isOwner()) {
            return false;
        }

        return $subscriber->subscriptions()
            ->whereHas('generator', fn ($q) => $q->where('owner_id', $user->id))
            ->exists();
    }
}
