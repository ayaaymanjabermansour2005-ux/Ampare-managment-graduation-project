<?php

namespace App\Listeners;

use App\Events\TechnicianTaskSubmitted;
use App\Notifications\TechnicianTaskSubmittedNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTechnicianTaskSubmittedNotification implements ShouldQueue
{
    public function handle(TechnicianTaskSubmitted $event): void
    {
        $recipient = $event->task->generator?->owner;

        if (NotificationPreferenceGate::allows($recipient, 'notify_technician_task_submitted')) {
            $recipient->notify(new TechnicianTaskSubmittedNotification($event->task));
        }
    }
}
