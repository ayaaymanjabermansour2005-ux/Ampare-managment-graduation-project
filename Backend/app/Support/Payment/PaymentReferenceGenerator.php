<?php

namespace App\Support\Payment;

use App\Models\Payment;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentReferenceGenerator
{
    private const PREFIX = 'PAY-';

    private const MAX_ATTEMPTS = 5;

    public function generate(): string
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $reference = self::PREFIX.Str::upper(Str::random(10));

            if (! Payment::withTrashed()->where('transaction_reference', $reference)->exists()) {
                return $reference;
            }
        }

        throw new RuntimeException('تعذّر توليد رقم مرجعي فريد للدفعة بعد عدة محاولات متتالية.');
    }
}
