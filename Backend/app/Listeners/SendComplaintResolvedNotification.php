<?php

namespace App\Listeners;

use App\Events\ComplaintResolved;
use App\Notifications\ComplaintResolvedNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendComplaintResolvedNotification implements ShouldQueue
{
    public function handle(ComplaintResolved $event): void
    {
        $recipient = $event->complaint->submitter;

        if (NotificationPreferenceGate::allows($recipient, 'notify_complaint_resolved')) {
            $recipient->notify(new ComplaintResolvedNotification($event->complaint));
        }
    }
}
