<?php

namespace App\Actions\Subscriber;

use App\Models\Subscriber;
use App\Models\User;

final class UpdateBeneficiaryTypeAction
{
    public function execute(Subscriber $subscriber, string $beneficiaryType, User $changedBy): Subscriber
    {
        $subscriber->update([
            'beneficiary_type' => $beneficiaryType,
            'beneficiary_type_changed_by' => $changedBy->id,
            'beneficiary_type_changed_at' => now(),
        ]);

        return $subscriber->fresh(['beneficiaryTypeChangedBy']);
    }
}
