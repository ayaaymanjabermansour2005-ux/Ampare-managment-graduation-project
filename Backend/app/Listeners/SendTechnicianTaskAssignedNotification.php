<?php

namespace App\Listeners;

use App\Events\TechnicianTaskAssigned;
use App\Notifications\TechnicianTaskAssignedNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTechnicianTaskAssignedNotification implements ShouldQueue
{
    public function handle(TechnicianTaskAssigned $event): void
    {
        $recipient = $event->task->technician?->user;

        if (NotificationPreferenceGate::allows($recipient, 'notify_technician_task_assigned')) {
            $recipient->notify(new TechnicianTaskAssignedNotification($event->task));
        }
    }
}
