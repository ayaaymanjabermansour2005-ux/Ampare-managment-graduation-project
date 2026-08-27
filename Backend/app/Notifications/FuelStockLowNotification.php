<?php

namespace App\Notifications;

use App\Models\Generator;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FuelStockLowNotification extends Notification
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(public Generator $generator, public array $status) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', ...$this->whatsAppChannelIfAvailable($notifiable)];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تنبيه: مخزون الوقود منخفض',
            'message' => sprintf(
                'مستوى الوقود بمولد "%s" وصل %.1f%% من السعة، يُنصح بالتزود بالوقود قريبًا.',
                $this->generator->name,
                $this->status['percentage']
            ),
            'generator_id' => $this->generator->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "⛽ تنبيه مخزون وقود منخفض\nالمولد: %s\nالمستوى الحالي: %.1f%%\nيُنصح بالتزود بالوقود قريبًا لتفادي انقطاع الخدمة.",
            $this->generator->name,
            $this->status['percentage']
        );
    }
}
