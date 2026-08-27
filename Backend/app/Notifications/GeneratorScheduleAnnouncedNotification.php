<?php

namespace App\Notifications;

use App\Models\GeneratorSchedule;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GeneratorScheduleAnnouncedNotification extends Notification
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(public GeneratorSchedule $schedule) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', ...$this->whatsAppChannelIfAvailable($notifiable)];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'جدول تشغيل جديد',
            'message' => sprintf(
                'المولد "%s" سيعمل من %s إلى %s.',
                $this->schedule->generator->name,
                $this->schedule->starts_at->format('H:i'),
                $this->schedule->ends_at->format('H:i')
            ),
            'generator_id' => $this->schedule->generator_id,
            'schedule_id' => $this->schedule->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "🔌 جدول تشغيل جديد\nالمولد: %s\nمن %s إلى %s.",
            $this->schedule->generator->name,
            $this->schedule->starts_at->format('H:i'),
            $this->schedule->ends_at->format('H:i')
        );
    }
}
