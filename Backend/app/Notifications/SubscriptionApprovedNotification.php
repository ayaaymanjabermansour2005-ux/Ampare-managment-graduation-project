<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SubscriptionApprovedNotification extends Notification
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(
        protected Subscription $subscription
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', ...$this->whatsAppChannelIfAvailable($notifiable)];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تمت الموافقة على اشتراكك',
            'message' => "تمت الموافقة على طلب اشتراكك بالمولد \"{$this->subscription->generator?->name}\".",
            'link_type' => 'subscription',
            'link_id' => $this->subscription->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "🎉 تمت الموافقة على اشتراكك\nالمولد: %s\nصار اشتراكك فعّال، تقدر تتابع تفاصيله من لوحة التحكم.",
            $this->subscription->generator?->name ?? '—'
        );
    }
}
