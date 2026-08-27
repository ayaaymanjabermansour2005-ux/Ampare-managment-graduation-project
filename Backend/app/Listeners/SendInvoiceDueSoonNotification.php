<?php

namespace App\Listeners;

use App\Events\InvoiceDueSoon;
use App\Notifications\InvoiceDueSoonNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvoiceDueSoonNotification implements ShouldQueue
{
    public function handle(InvoiceDueSoon $event): void
    {
        $subscriber = $event->invoice->subscription?->subscriberMeter?->subscriber?->user;

        if (! $subscriber) {
            return;
        }

        if (NotificationPreferenceGate::allows($subscriber, 'notify_invoice_due_soon')) {
            $subscriber->notify(new InvoiceDueSoonNotification($event->invoice));
        }
    }
}
