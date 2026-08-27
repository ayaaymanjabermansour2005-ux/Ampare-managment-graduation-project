<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.view')) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        $subscription = $payment->invoice?->subscription;
        if ($user->isOwner()) {
            return (int) $user->id ===
                (int) $subscription?->generator?->owner_id;
        }
        if ($user->isSubscriber()) {
            return (int) $user->id ===
                (int) $subscription
                    ?->subscriberMeter
                    ?->subscriber
                    ?->user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create')
            &&
            (
                $user->isSubscriber()
                ||
                $user->isAdmin()
            );
    }

    public function approve(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.approve')) {
            return false;
        }

        return $this->canReview($user, $payment);
    }

    public function reject(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.reject')) {
            return false;
        }

        return $this->canReview($user, $payment);
    }

    public function needsCorrection(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.approve')) {
            return false;
        }

        return $this->canReview($user, $payment) && $payment->status === PaymentStatus::Pending;
    }

    public function resubmit(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.create') || ! $user->isSubscriber()) {
            return false;
        }

        $subscriberId = $payment
            ->invoice
            ?->subscription
            ?->subscriberMeter
            ?->subscriber
            ?->user_id;

        return (int) $subscriberId === (int) $user->id
            && $payment->status === PaymentStatus::NeedsCorrection;
    }

    public function cancel(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.create') || ! $user->isSubscriber()) {
            return false;
        }

        $subscriberId = $payment
            ->invoice
            ?->subscription
            ?->subscriberMeter
            ?->subscriber
            ?->user_id;

        return (int) $subscriberId === (int) $user->id
            && $payment->status->canBeDeleted();
    }

    private function canReview(
        User $user,
        Payment $payment
    ): bool {

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return (int) $user->id ===
                (int) $payment
                    ->invoice
                    ?->subscription
                    ?->generator
                    ?->owner_id;
        }

        return false;
    }

    public function manageAttachments(
        User $user,
        Payment $payment
    ): bool {

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSubscriber()) {

            $subscriberId =
                $payment
                ->invoice
                ?->subscription
                ?->subscriberMeter
                ?->subscriber
                ?->user_id;

            return (int) $subscriberId === (int) $user->id
                &&
                in_array(
                    $payment->status,
                    [
                        PaymentStatus::Pending,
                        PaymentStatus::NeedsCorrection,
                    ],
                    true
                );
        }

        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    public function update(
        User $user,
        Payment $payment
    ): bool {

        return $user->isAdmin();
    }
}
