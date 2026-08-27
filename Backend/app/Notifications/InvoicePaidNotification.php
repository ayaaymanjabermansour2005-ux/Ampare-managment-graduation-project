<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class InvoicePaidNotification extends Notification
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(
        protected Invoice $invoice
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', ...$this->whatsAppChannelIfAvailable($notifiable)];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تم سداد الفاتورة',
            'message' => "تم سداد الفاتورة رقم #{$this->invoice->id} بالكامل بقيمة {$this->invoice->final_amount}.",
            'link_type' => 'invoice',
            'link_id' => $this->invoice->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'تم سداد الفاتورة',
            'message' => "تم سداد الفاتورة رقم #{$this->invoice->id}",
            'link_type' => 'invoice',
            'link_id' => $this->invoice->id,
        ]);
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "🧾 تم سداد فاتورتك بالكامل\nرقم الفاتورة: #%d\nالمبلغ: %s\nشكرًا لالتزامك بالسداد.",
            $this->invoice->id,
            $this->invoice->final_amount
        );
    }
}
