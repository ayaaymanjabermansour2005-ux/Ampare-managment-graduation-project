<?php

namespace App\Console\Commands;

use App\Services\InvoiceService;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'يحوّل الفواتير المتأخرة لحالة Overdue بعد تجاوز مهلة السداد المحددة';

    public function handle(InvoiceService $invoiceService): int
    {
        $count = $invoiceService->markOverdueInvoices();

        $this->info("تم تحديث {$count} فاتورة إلى حالة Overdue.");

        return self::SUCCESS;
    }
}
