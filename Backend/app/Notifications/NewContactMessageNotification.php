<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewContactMessageNotification extends Notification
{
    use Queueable;

    public function __construct(protected ContactMessage $contactMessage) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'رسالة تواصل جديدة',
            'message' => "{$this->contactMessage->name} أرسل رسالة جديدة: \"{$this->contactMessage->subject?->label()}\"",
            'link_type' => 'contact_message',
            'link_id' => $this->contactMessage->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
