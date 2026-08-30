<?php

namespace App\Actions\User;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceDueSoon;
use App\Models\Invoice;

final class SendBulkPaymentReminderAction
{
    /**
     * @param  array<int>  $subscriberIds
     * @return int عدد الفواتير التي أُرسل تذكير بشأنها فعليًا
     */
    public function execute(array $subscriberIds): int
    {
        if (empty($subscriberIds)) {
            return 0;
        }

        $invoices = Invoice::query()
            ->whereIn('status', [InvoiceStatus::Pending->value, InvoiceStatus::Overdue->value])
            ->whereHas(
                'subscription.subscriberMeter.subscriber.user',
                fn ($q) => $q->whereIn('users.id', $subscriberIds)
            )
            ->with('subscription.subscriberMeter.subscriber.user')
            ->get();

        foreach ($invoices as $invoice) {
            InvoiceDueSoon::dispatch($invoice);
        }

        return $invoices->count();
    }
}
