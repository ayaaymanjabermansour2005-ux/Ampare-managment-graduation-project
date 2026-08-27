<?php

namespace App\Listeners;

use App\Events\TechnicianTaskApproved;
use App\Events\TechnicianTaskRejected;
use App\Notifications\TechnicianTaskApprovedNotification;
use App\Notifications\TechnicianTaskRejectedNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTechnicianTaskReviewedNotification implements ShouldQueue
{
    public function handleApproved(TechnicianTaskApproved $event): void
    {
        $recipient = $event->task->technician?->user;

        if (NotificationPreferenceGate::allows($recipient, 'notify_technician_task_approved')) {
            $recipient->notify(new TechnicianTaskApprovedNotification($event->task));
        }
    }

    public function handleRejected(TechnicianTaskRejected $event): void
    {
        $recipient = $event->task->technician?->user;

        if (NotificationPreferenceGate::allows($recipient, 'notify_technician_task_rejected')) {
            $recipient->notify(new TechnicianTaskRejectedNotification($event->task));
        }
    }
}
