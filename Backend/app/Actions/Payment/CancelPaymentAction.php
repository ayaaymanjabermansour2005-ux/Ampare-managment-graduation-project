<?php

namespace App\Actions\Payment;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelPaymentAction
{
    public function execute(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if (! $payment->status->canBeDeleted()) {
                throw ValidationException::withMessages([
                    'status' => ['يمكن إلغاء الدفعات المعلقة أو التي تحتاج تعديل فقط.'],
                ]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Cancelled,
            ])->save();

            return $payment->fresh(['paymentMethod']);
        });
    }
}
