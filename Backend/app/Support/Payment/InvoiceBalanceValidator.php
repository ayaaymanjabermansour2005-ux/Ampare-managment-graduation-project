<?php

namespace App\Support\Payment;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Support\Money;

final class InvoiceBalanceValidator
{
    private const SCALE = 2;

    public function remainingIls(Invoice $invoice, ?int $excludingPaymentId = null): float
    {
        $paidIls = (float) $invoice->payments()
            ->where('status', PaymentStatus::Paid)
            ->when(
                $excludingPaymentId,
                fn ($q) => $q->where('id', '!=', $excludingPaymentId)
            )
            ->sum('amount_ils');

        return Money::max(
            0.0,
            Money::sub((float) $invoice->final_amount_ils, $paidIls, self::SCALE),
            self::SCALE
        );
    }

    public function isWithinRemainingBalance(
        Invoice $invoice,
        float $amountIls,
        ?int $excludingPaymentId = null
    ): bool {
        return Money::lessThanOrEqual(
            $amountIls,
            $this->remainingIls($invoice, $excludingPaymentId),
            self::SCALE
        );
    }
}
