<?php

namespace App\Policies;

use App\Models\SubscriberMeter;
use App\Models\User;

class SubscriberMeterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('subscriber-meters.view') && ($user->isSubscriber() || $user->isAdmin());
    }

    public function view(User $user, SubscriberMeter $meter): bool
    {
        if (! $user->can('subscriber-meters.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isSubscriber() && $meter->subscriber?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        if (! $user->can('subscriber-meters.create')) {
            return false;
        }

        // الأدمن بيقدر يسجّل عداد جديد نيابةً عن مشترك (مثلًا لما يكون
        // المشترك ما إله ولا عداد بعد ويحاول الأدمن ينشئله اشتراك يدويًا).
        return $user->isSubscriber() || $user->isAdmin();
    }

    public function update(User $user, SubscriberMeter $meter): bool
    {
        if (! $user->can('subscriber-meters.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isSubscriber() && $meter->subscriber?->user_id === $user->id;
    }

    public function delete(User $user, SubscriberMeter $meter): bool
    {
        if (! $user->can('subscriber-meters.delete')) {
            return false;
        }

        return ($user->isSubscriber() && $meter->subscriber?->user_id === $user->id) || $user->isAdmin();
    }
}
