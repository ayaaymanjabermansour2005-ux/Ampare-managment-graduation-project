<?php

namespace App\Actions\Payment;

use App\Enums\PaymentReviewStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Support\Eloquent\FreshOrFail;
use App\Support\Payment\PaymentReviewLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RequestPaymentCorrectionAction
{
    public function __construct(
        private readonly PaymentReviewLogger $reviewLogger,
    ) {}

    public function execute(Payment $payment, User $reviewer, string $note): Payment
    {
        return DB::transaction(function () use ($payment, $reviewer, $note) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => ['هذه الدفعة ليست بحاجة لتعديل.'],
                ]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::NeedsCorrection,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            $this->reviewLogger->log($payment, $reviewer, PaymentReviewStatus::NeedsCorrection, $note);

            return FreshOrFail::reload($payment, ['invoice']);
        });
    }
}
