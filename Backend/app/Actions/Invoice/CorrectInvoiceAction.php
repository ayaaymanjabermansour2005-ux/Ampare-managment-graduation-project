<?php

namespace App\Actions\Invoice;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CorrectInvoiceAction
{
    public function execute(Invoice $invoice, float $newFinalAmount, string $reason, User $admin): Invoice
    {
        return DB::transaction(function () use ($invoice, $newFinalAmount, $reason, $admin) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::PartiallyPaid], true)) {
                throw ValidationException::withMessages([
                    'invoice' => ['لا يمكن تصحيح فاتورة عليها دفعات مقبولة بالفعل — استخدمي دفعة تسوية بدلًا من ذلك.'],
                ]);
            }

            $oldAmount = (float) $invoice->final_amount;
            $roundedAmount = Money::round($newFinalAmount, 2);

            $newAmountIls = $invoice->currency === Currency::ILS
                ? $roundedAmount
                : Money::round($roundedAmount * (float) ($invoice->exchange_rate ?? 1), 2);

            $invoice->update([
                'final_amount' => $roundedAmount,
                'final_amount_ils' => $newAmountIls,
            ]);

            activity()
                ->causedBy($admin)
                ->performedOn($invoice)
                ->withProperties(['old_amount' => $oldAmount, 'new_amount' => $newFinalAmount, 'reason' => $reason])
                ->log('admin_corrected_invoice_amount');

            return $invoice->fresh();
        });
    }
}
