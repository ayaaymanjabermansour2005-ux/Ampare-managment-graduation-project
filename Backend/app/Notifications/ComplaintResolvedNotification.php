<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ComplaintResolvedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Complaint $complaint) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تم حل شكواك',
            'message' => "تم حل الشكوى \"{$this->complaint->subject}\".",
            'link_type' => 'complaint',
            'link_id' => $this->complaint->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
