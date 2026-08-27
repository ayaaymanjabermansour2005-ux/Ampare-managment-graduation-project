<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Notifications\InvoicePaidNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvoicePaidNotification implements ShouldQueue
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice->loadMissing('subscription.subscriberMeter.subscriber.user', 'subscription.generator.owner');
        $subscription = $invoice->subscription;

        $subscriberUser = $subscription?->subscriberMeter?->subscriber?->user;
        if (NotificationPreferenceGate::allows($subscriberUser, 'notify_invoice_paid')) {
            $subscriberUser->notify(new InvoicePaidNotification($invoice));
        }

        $ownerUser = $subscription?->generator?->owner;
        if (NotificationPreferenceGate::allows($ownerUser, 'notify_invoice_paid')) {
            $ownerUser->notify(new InvoicePaidNotification($invoice));
        }
    }
}
