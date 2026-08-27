<?php

namespace App\Support\Payment;

use App\Enums\PaymentReviewStatus;
use App\Models\Payment;
use App\Models\PaymentReview;
use App\Models\User;

class PaymentReviewLogger
{
    public function log(Payment $payment, User $reviewer, PaymentReviewStatus $status, ?string $reason = null): void
    {
        PaymentReview::create([
            'payment_id' => $payment->id,
            'reviewed_by' => $reviewer->id,
            'status' => $status,
            'reason' => $reason,
        ]);
    }
}
