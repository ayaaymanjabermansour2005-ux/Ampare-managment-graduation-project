<?php

namespace App\Actions\TechnicianPayment;

use App\Enums\TechnicianPaymentStatus;
use App\Models\TechnicianPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApproveTechnicianPaymentAction
{
    public function execute(TechnicianPayment $payment, User $technician): TechnicianPayment
    {
        return DB::transaction(function () use ($payment, $technician) {
            $payment = TechnicianPayment::lockForUpdate()->findOrFail($payment->id);

            if (! $payment->status->isReviewable()) {
                throw ValidationException::withMessages([
                    'status' => ['هذه الدفعة ليست بانتظار التأكيد.'],
                ]);
            }

            $payment->forceFill([
                'status' => TechnicianPaymentStatus::Approved,
                'reviewed_by' => $technician->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();

            return $payment->fresh(['technician.user', 'owner', 'paymentMethod', 'reviewedBy']);
        });
    }
}
