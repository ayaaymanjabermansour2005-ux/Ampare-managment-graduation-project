<?php

namespace App\Notifications;

use App\Models\TechnicianTask;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TechnicianTaskApprovedNotification extends Notification implements ShouldQueue
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(public readonly TechnicianTask $task) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', ...$this->whatsAppChannelIfAvailable($notifiable)];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'generator_id' => $this->task->generator_id,
            'link_type' => 'technician_task',
            'link_id' => $this->task->id,
            'title' => 'تم اعتماد أمر الشغل',
            'message' => 'تم اعتماد عملك على أمر الشغل بنجاح.',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "✅ تم اعتماد أمر شغلك\nالمولد: %s\nتم اعتماد عملك بنجاح، شكرًا لجهودك.",
            $this->task->generator?->name ?? '—'
        );
    }
}
