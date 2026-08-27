<?php

namespace App\Listeners;

use App\Events\PaymentSubmitted;
use App\Models\User;
use App\Notifications\PaymentSubmittedNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentSubmittedNotification implements ShouldQueue
{
    public function handle(PaymentSubmitted $event): void
    {
        $ownerId = $event->payment->invoice?->subscription?->generator?->owner_id;

        if (! $ownerId) {
            return;
        }

        $recipient = User::find($ownerId);

        if (NotificationPreferenceGate::allows($recipient, 'notify_payment_submitted')) {
            $recipient->notify(new PaymentSubmittedNotification($event->payment));
        }
    }
}
