<?php

namespace App\Policies;

use App\Models\SubscriptionServiceRequest;
use App\Models\User;

class SubscriptionServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service-requests.view')
            && ($user->isAdmin() || $user->isOwner() || $user->isSubscriber());
    }

    public function view(User $user, SubscriptionServiceRequest $serviceRequest): bool
    {
        if (! $user->can('service-requests.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $serviceRequest->subscription->generator->owner_id === $user->id;
        }

        if ($user->isSubscriber()) {
            return $serviceRequest->requested_by === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('service-requests.create') && $user->isSubscriber();
    }

    public function review(User $user, SubscriptionServiceRequest $serviceRequest): bool
    {
        if (! $user->can('service-requests.review') || ! $serviceRequest->isPending()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner()
            && $serviceRequest->subscription->generator->owner_id === $user->id;
    }

    public function cancel(User $user, SubscriptionServiceRequest $serviceRequest): bool
    {
        return $user->can('service-requests.cancel')
            && $serviceRequest->requested_by === $user->id
            && $serviceRequest->isPending();
    }
}
