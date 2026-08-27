<?php

namespace App\Actions\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReissueInvoiceAction
{
    public function execute(Invoice $invoice, User $admin): Invoice
    {
        return DB::transaction(function () use ($invoice, $admin) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->status !== InvoiceStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'invoice' => ['لا يمكن إعادة إصدار إلا فاتورة ملغية.'],
                ]);
            }

            $newInvoice = Invoice::create([
                'subscription_id' => $invoice->subscription_id,
                'meter_reading_id' => null,
                'service_request_id' => null,
                'amount' => $invoice->amount,
                'discount_amount' => $invoice->discount_amount,
                'discount_id' => $invoice->discount_id,
                'final_amount' => $invoice->final_amount,
                'final_amount_ils' => $invoice->final_amount_ils,
                'currency' => $invoice->currency,
                'exchange_rate' => $invoice->exchange_rate,
                'due_date' => now()->addDays((int) config('billing.invoice_due_days', 7)),
            ]);

            activity()
                ->causedBy($admin)
                ->performedOn($newInvoice)
                ->withProperties([
                    'reissued_from_invoice_id' => $invoice->id,
                    'original_service_request_id' => $invoice->service_request_id,
                    'original_meter_reading_id' => $invoice->meter_reading_id,
                ])
                ->log('admin_reissued_invoice');

            return $newInvoice->fresh();
        });
    }
}
