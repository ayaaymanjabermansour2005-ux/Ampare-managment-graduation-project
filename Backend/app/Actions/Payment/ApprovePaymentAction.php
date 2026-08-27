<?php

namespace App\Actions\Payment;

use App\Enums\PaymentReviewStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceService;
use App\Support\Payment\PaymentReviewLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApprovePaymentAction
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly PaymentReviewLogger $reviewLogger,
    ) {}

    public function execute(Payment $payment, User $approver): Payment
    {
        return DB::transaction(function () use ($payment, $approver) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if (! $payment->status->isReviewable()) {
                throw ValidationException::withMessages([
                    'status' => ['هذه الدفعة ليست بانتظار المراجعة.'],
                ]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'processed_by' => $approver->id,
                'reviewed_by' => $approver->id,
                'reviewed_at' => now(),
                'review_note' => null,
            ])->save();

            $this->reviewLogger->log($payment, $approver, PaymentReviewStatus::Approved);

            $this->invoiceService->recalculateStatus($payment->invoice);

            return $payment->fresh(['invoice', 'processedBy']);
        });
    }
}
