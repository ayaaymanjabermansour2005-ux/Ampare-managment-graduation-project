<?php

namespace App\Actions\TechnicianPayment;

use App\DTOs\TechnicianPayment\CreateTechnicianPaymentData;
use App\Models\Technician;
use App\Models\TechnicianPayment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class CreateTechnicianPaymentAction
{
    public function execute(CreateTechnicianPaymentData $data, User $owner): TechnicianPayment
    {
        $technician = Technician::findOrFail($data->technicianId);

        if ($technician->owner_id !== $owner->id) {
            throw ValidationException::withMessages([
                'technician_id' => ['هذا الفني ليس تابعًا لك.'],
            ]);
        }

        $payment = TechnicianPayment::create([
            'technician_id' => $technician->id,
            'owner_id' => $owner->id,
            'payment_method_id' => $data->paymentMethodId,
            'amount' => $data->amount,
            'currency' => $data->currency,
            'note' => $data->note,
            'created_by' => $owner->id,
        ]);

        return $payment->fresh(['technician.user', 'paymentMethod']);
    }
}
