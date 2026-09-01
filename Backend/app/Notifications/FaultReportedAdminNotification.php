<?php

namespace App\Notifications;

use App\Models\Fault;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FaultReportedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Fault $fault) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'عطل جديد',
            'message' => "تم تسجيل عطل جديد: \"{$this->fault->title}\" على مولد \"{$this->fault->generator?->name}\" (المالك: {$this->fault->generator?->owner?->name}).",
            'link_type' => 'fault',
            'link_id' => $this->fault->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
