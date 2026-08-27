<?php

namespace App\Policies;

use App\Enums\GeneratorStatus;
use App\Enums\OfferStatus;
use App\Enums\OfferTargetMode;
use App\Models\Offer;
use App\Models\User;

class OfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('offers.view')
            && ($user->isAdmin() || $user->isOwner() || $user->isSubscriber());
    }

    public function view(User $user, Offer $offer): bool
    {
        if (! $user->can('offers.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $offer->owner_id === $user->id;
        }

        if ($user->isSubscriber()) {
            return $this->offerVisibleToSubscriber($offer, $user);
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->can('offers.create') || ! $user->isOwner()) {
            return false;
        }

        return $user->generators()->where('status', GeneratorStatus::Active->value)->exists();
    }

    public function update(User $user, Offer $offer): bool
    {
        if (! $user->can('offers.update')) {
            return false;
        }
        return $user->isOwner()
            && $offer->owner_id === $user->id
            && $offer->status === OfferStatus::Active;
    }

    public function cancel(User $user, Offer $offer): bool
    {
        if (! $user->can('offers.cancel')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $offer->owner_id === $user->id;
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $user->can('offers.delete') && $user->isAdmin();
    }

    private function offerVisibleToSubscriber(Offer $offer, User $user): bool
    {
        $subscriber = $user->subscriber;

        if (! $subscriber) {
            return false;
        }

        $isRelatedToOwner = $subscriber->subscriptions()
            ->whereHas('generator', fn($g) => $g->where('owner_id', $offer->owner_id))
            ->exists();

        if (! $isRelatedToOwner) {
            return false;
        }

        return match ($offer->target_mode) {
            OfferTargetMode::All => true,
            OfferTargetMode::Beneficiary => $offer->beneficiary_type === $subscriber->beneficiary_type,
            OfferTargetMode::Selected => $offer->targetedSubscribers()->where('subscribers.id', $subscriber->id)->exists(),
        };
    }
}
