<?php

namespace App\Notifications;

use App\Models\TechnicianTask;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TechnicianTaskRejectedNotification extends Notification implements ShouldQueue
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
            'rejection_reason' => $this->task->rejection_reason,
            'title' => 'تم رفض أمر الشغل',
            'message' => 'تم رفض عملك على أمر الشغل، راجع سبب الرفض.',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "❌ تم رفض أمر شغلك\nالمولد: %s\nالسبب: %s\nيرجى مراجعة التفاصيل واتخاذ الإجراء اللازم بأسرع وقت.",
            $this->task->generator?->name ?? '—',
            $this->task->rejection_reason ?? '—'
        );
    }
}
