<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PaymentSubmittedNotification extends Notification
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(protected Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', ...$this->whatsAppChannelIfAvailable($notifiable)];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'دفعة جديدة بانتظار المراجعة',
            'message' => "وصلتك دفعة بقيمة {$this->payment->amount} بانتظار مراجعتك على الفاتورة رقم #{$this->payment->invoice_id}.",
            'link_type' => 'payment',
            'link_id' => $this->payment->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "💰 دفعة جديدة بانتظار المراجعة\nالمبلغ: %s\nرقم الفاتورة: #%d\nيرجى مراجعتها بلوحة التحكم.",
            $this->payment->amount,
            $this->payment->invoice_id
        );
    }
}
