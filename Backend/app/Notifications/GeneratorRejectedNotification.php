<?php

namespace App\Notifications;

use App\Models\Generator;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GeneratorRejectedNotification extends Notification
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(public Generator $generator) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', ...$this->whatsAppChannelIfAvailable($notifiable)];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تم رفض اعتماد مولّدك',
            'message' => "لم يتم اعتماد مولّدك \"{$this->generator->name}\". السبب: {$this->generator->rejection_reason}",
            'link_type' => 'generator',
            'link_id' => $this->generator->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "⚠️ لم يتم اعتماد مولّدك\nالمولد: %s\nالسبب: %s\nيرجى مراجعة الملاحظات وتحديث المستندات ثم إعادة التقديم.",
            $this->generator->name,
            $this->generator->rejection_reason ?? '—'
        );
    }
}
