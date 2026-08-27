<?php

namespace App\Notifications;

use App\Models\Fault;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FaultReportedNotification extends Notification
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(
        protected Fault $fault
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', ...$this->whatsAppChannelIfAvailable($notifiable)];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'عطل جديد على مولدك',
            'message' => "تم تسجيل عطل جديد: \"{$this->fault->title}\" على مولدك \"{$this->fault->generator?->name}\".",
            'link_type' => 'fault',
            'link_id' => $this->fault->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "⚠️ عطل جديد على مولدك\nالمولد: %s\nالعنوان: %s\nيرجى مراجعة لوحة التحكم للتفاصيل والتحقق منه.",
            $this->fault->generator?->name ?? '—',
            $this->fault->title
        );
    }
}
