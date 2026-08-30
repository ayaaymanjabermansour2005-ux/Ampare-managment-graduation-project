<?php

namespace App\Notifications;

use App\Models\OwnerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OwnerApplicationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected OwnerApplication $application,
        protected ?string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (filled($this->application->phone)) {
            $channels[] = 'whatsapp';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('بخصوص طلب انضمامك كصاحب مولد - أمبير')
            ->greeting('مرحبًا '.$this->application->name)
            ->line('راجعنا طلب انضمامك كصاحب مولد على منصة أمبير، وللأسف ما قدرنا نوافق عليه بهاي المرحلة.');

        if (! blank($this->reason)) {
            $message->line('السبب: '.$this->reason);
        }

        return $message
            ->line('إذا حابب تعيد التقديم بمعلومات محدّثة أو عندك أي استفسار، تقدر تتواصل معنا أو تعيد إرسال الطلب من جديد.');
    }

    public function toWhatsApp(object $notifiable): string
    {
        $message = "مرحبًا {$this->application->name}،\nللأسف ما قدرنا نوافق حاليًا على طلب انضمامك كصاحب مولد بمنصة أمبير.";

        if (! blank($this->reason)) {
            $message .= "\nالسبب: {$this->reason}";
        }

        return $message."\nتقدر تتواصل معنا أو تعيد تقديم الطلب ببيانات محدّثة.";
    }
}
