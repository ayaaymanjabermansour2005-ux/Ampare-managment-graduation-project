<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\SubscriptionMeterTransferRequest;
use App\Models\User;

class SubscriptionMeterTransferRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('subscription-meter-transfers.view')
            && ($user->isAdmin() || $user->isOwner() || $user->isSubscriber());
    }

    public function view(User $user, SubscriptionMeterTransferRequest $transferRequest): bool
    {
        if (! $user->can('subscription-meter-transfers.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $transferRequest->subscription?->generator?->owner_id === $user->id;
        }

        if ($user->isSubscriber()) {
            return $transferRequest->requested_by === $user->id
                || $transferRequest->subscription?->subscriberMeter?->subscriber?->user_id === $user->id;
        }

        return false;
    }

    /**
     * إنشاء طلب نقل عداد — يتحقق أن الاشتراك المستهدف يخص المشترك صاحب الطلب فعلاً.
     * (التحقق الدقيق من ملكية "العداد الجديد" نفسه يتم داخل الـ Action بعد
     * التحميل، لأنه ليس Route-Model-Bound هون.)
     */
    public function create(User $user, Subscription $subscription): bool
    {
        if (! $user->can('subscription-meter-transfers.create') || ! $user->isSubscriber()) {
            return false;
        }

        return $subscription->subscriberMeter?->subscriber?->user_id === $user->id;
    }

    public function approve(User $user, SubscriptionMeterTransferRequest $transferRequest): bool
    {
        if (! $user->can('subscription-meter-transfers.approve') || ! $transferRequest->status->isReviewable()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $transferRequest->subscription?->generator?->owner_id === $user->id;
    }

    public function reject(User $user, SubscriptionMeterTransferRequest $transferRequest): bool
    {
        if (! $user->can('subscription-meter-transfers.reject') || ! $transferRequest->status->isReviewable()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $transferRequest->subscription?->generator?->owner_id === $user->id;
    }
}
