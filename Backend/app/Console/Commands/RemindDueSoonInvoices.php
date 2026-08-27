<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceDueSoon;
use App\Models\Invoice;
use Illuminate\Console\Command;

class RemindDueSoonInvoices extends Command
{
    protected $signature = 'invoices:remind-due-soon {--days=3}';

    protected $description = 'يرسل تذكيرًا للمشتركين الذين لديهم فواتير مستحقة خلال أيام محدَّدة';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $targetDate = now()->addDays($days)->toDateString();

        $invoices = Invoice::query()
            ->where('status', InvoiceStatus::Pending->value)
            ->whereDate('due_date', $targetDate)
            ->with('subscription.subscriberMeter.subscriber.user')
            ->get();

        foreach ($invoices as $invoice) {
            InvoiceDueSoon::dispatch($invoice);
        }

        $this->info("تم إرسال {$invoices->count()} تذكير فاتورة.");

        return self::SUCCESS;
    }
}
