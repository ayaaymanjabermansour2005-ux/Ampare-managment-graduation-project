<?php

namespace App\Notifications;

use App\Models\Generator;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GeneratorVerifiedNotification extends Notification
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
            'title' => 'تم اعتماد مولّدك',
            'message' => "تم التحقق من مستندات مولّدك \"{$this->generator->name}\" واعتماده — أصبح نشطًا الآن.",
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
            "🎉 تم اعتماد مولّدك\nالمولد: %s\nتم التحقق من مستنداته واعتماده، وأصبح نشطًا الآن — تقدر تستقبل مشتركين.",
            $this->generator->name
        );
    }
}
