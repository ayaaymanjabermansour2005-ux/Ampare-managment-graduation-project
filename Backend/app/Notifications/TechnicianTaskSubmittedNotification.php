<?php

namespace App\Notifications;

use App\Models\TechnicianTask;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TechnicianTaskSubmittedNotification extends Notification implements ShouldQueue
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
            'title' => 'أمر شغل بانتظار المراجعة',
            'message' => 'الفني أنهى العمل على أمر الشغل، بانتظار مراجعتك.',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "✅ أمر شغل بانتظار مراجعتك\nالمولد: %s\nالفني أنهى العمل، يرجى المراجعة بلوحة التحكم.",
            $this->task->generator?->name ?? '—'
        );
    }
}
