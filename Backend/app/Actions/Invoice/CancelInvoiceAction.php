<?php

namespace App\Actions\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelInvoiceAction
{
    public function execute(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::PartiallyPaid], true)) {
                throw ValidationException::withMessages([
                    'invoice' => ['لا يمكن إلغاء فاتورة عليها دفعات مقبولة بالفعل.'],
                ]);
            }

            $invoice->forceFill(['status' => InvoiceStatus::Cancelled])->save();

            return $invoice->fresh();
        });
    }
}
