<?php

namespace App\Notifications;

use App\Models\TechnicianTask;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TechnicianTaskAssignedNotification extends Notification implements ShouldQueue
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
            'type' => $this->task->type->value,
            'title' => 'أمر شغل جديد',
            'message' => 'تم تعيينك على أمر شغل جديد: ' . $this->task->type->label(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "🔧 أمر شغل جديد\nالنوع: %s\nالمولد: %s\nيرجى مراجعة التفاصيل بلوحة التحكم.",
            $this->task->type->label(),
            $this->task->generator?->name ?? '—'
        );
    }
}
