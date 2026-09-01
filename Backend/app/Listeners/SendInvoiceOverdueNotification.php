<?php

namespace App\Listeners;

use App\Enums\Role;
use App\Events\InvoiceOverdue;
use App\Models\User;
use App\Notifications\InvoiceOverdueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendInvoiceOverdueNotification implements ShouldQueue
{
    public function handle(InvoiceOverdue $event): void
    {
        $invoice = $event->invoice->loadMissing('subscription.subscriberMeter.subscriber.user');
        $admins = User::role(Role::ADMIN->value)->get();

        Notification::send($admins, new InvoiceOverdueNotification($invoice));
    }
}
