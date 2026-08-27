<?php

namespace App\Actions\TechnicianPayment;

use App\Enums\TechnicianPaymentStatus;
use App\Models\TechnicianPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RejectTechnicianPaymentAction
{
    public function execute(TechnicianPayment $payment, User $technician, string $reason): TechnicianPayment
    {
        return DB::transaction(function () use ($payment, $technician, $reason) {
            $payment = TechnicianPayment::lockForUpdate()->findOrFail($payment->id);

            if (! $payment->status->isReviewable()) {
                throw ValidationException::withMessages([
                    'status' => ['هذه الدفعة ليست بانتظار التأكيد.'],
                ]);
            }

            $payment->forceFill([
                'status' => TechnicianPaymentStatus::Rejected,
                'reviewed_by' => $technician->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return $payment->fresh(['technician.user', 'owner', 'paymentMethod', 'reviewedBy']);
        });
    }
}
