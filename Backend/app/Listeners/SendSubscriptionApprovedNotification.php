<?php

namespace App\Listeners;

use App\Events\SubscriptionApproved;
use App\Notifications\SubscriptionApprovedNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSubscriptionApprovedNotification implements ShouldQueue
{
    public function handle(SubscriptionApproved $event): void
    {
        $subscription = $event->subscription->loadMissing('subscriberMeter.subscriber.user', 'generator');

        $recipient = $subscription->subscriberMeter?->subscriber?->user;

        if (NotificationPreferenceGate::allows($recipient, 'notify_subscription_approved')) {
            $recipient->notify(new SubscriptionApprovedNotification($subscription));
        }
    }
}
