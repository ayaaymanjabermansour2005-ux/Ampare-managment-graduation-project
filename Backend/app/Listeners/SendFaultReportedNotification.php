<?php

namespace App\Listeners;

use App\Events\FaultReported;
use App\Notifications\FaultReportedNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFaultReportedNotification implements ShouldQueue
{
    public function handle(FaultReported $event): void
    {
        $fault = $event->fault->loadMissing('generator.owner');
        $recipient = $fault->generator?->owner;

        if (NotificationPreferenceGate::allows($recipient, 'notify_fault_reported')) {
            $recipient->notify(new FaultReportedNotification($fault));
        }
    }
}
