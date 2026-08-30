<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceDueSoonNotification extends Notification implements ShouldQueue
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', ...$this->whatsAppChannelIfAvailable($notifiable)];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('فاتورتك على وشك الاستحقاق')
            ->greeting('مرحبًا '.$notifiable->name)
            ->line("لديك فاتورة بقيمة {$this->invoice->final_amount} {$this->invoice->currency->value} مستحقة بتاريخ {$this->invoice->due_date->format('Y-m-d')}.")
            ->action('عرض الفاتورة', rtrim(config('app.frontend_url', config('app.url')), '/').'/invoices/'.$this->invoice->id)
            ->line('يرجى السداد قبل الموعد لتفادي أي تأخير.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'فاتورة على وشك الاستحقاق',
            'invoice_id' => $this->invoice->id,
            'due_date' => $this->invoice->due_date->format('Y-m-d'),
            'final_amount' => $this->invoice->final_amount,
        ];
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "🧾 تذكير: فاتورتك على وشك الاستحقاق\nالمبلغ: %s %s\nتاريخ الاستحقاق: %s\nيرجى السداد قبل الموعد لتفادي أي تأخير.",
            $this->invoice->final_amount,
            $this->invoice->currency->value,
            $this->invoice->due_date->format('Y-m-d')
        );
    }
}
