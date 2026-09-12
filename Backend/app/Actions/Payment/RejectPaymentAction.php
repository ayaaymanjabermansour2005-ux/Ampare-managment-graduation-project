<?php

namespace App\Actions\Payment;

use App\Enums\PaymentReviewStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentRejected;
use App\Models\Payment;
use App\Models\User;
use App\Support\Payment\PaymentReviewLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RejectPaymentAction
{
    public function __construct(
        private readonly PaymentReviewLogger $reviewLogger,
    ) {}

    public function execute(Payment $payment, User $reviewer, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $reviewer, $reason) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if (! $payment->status->isReviewable()) {
                throw ValidationException::withMessages([
                    'status' => ['هذه الدفعة ليست بانتظار المراجعة.'],
                ]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Rejected,
                'rejection_reason' => $reason,
                'processed_by' => $reviewer->id,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $reason,
            ])->save();

            $this->reviewLogger->log($payment, $reviewer, PaymentReviewStatus::Rejected, $reason);

            PaymentRejected::dispatch($payment);

            return $payment->fresh(['invoice', 'processedBy']);
        });
    }
}
