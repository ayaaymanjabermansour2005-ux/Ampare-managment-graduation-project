<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewComplaintNotification extends Notification implements ShouldQueue
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
            'title' => 'شكوى جديدة',
            'message' => "شكوى جديدة من {$this->complaint->submitter?->name}: \"{$this->complaint->subject}\"",
            'link_type' => 'complaint',
            'link_id' => $this->complaint->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
