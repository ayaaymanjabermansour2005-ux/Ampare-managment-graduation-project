<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $subscriberName = $this->invoice->subscription?->subscriberMeter?->subscriber?->user?->name ?? '—';

        return [
            'title' => 'فاتورة متأخرة السداد',
            'message' => "الفاتورة رقم {$this->invoice->id} الخاصة بالمشترك {$subscriberName} تجاوزت تاريخ الاستحقاق ولم تُسدَّد بعد.",
            'link_type' => 'invoice',
            'link_id' => $this->invoice->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
