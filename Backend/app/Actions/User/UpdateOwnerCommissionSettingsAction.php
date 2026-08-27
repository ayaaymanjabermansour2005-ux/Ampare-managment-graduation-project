<?php

namespace App\Actions\User;

use App\Enums\CommissionMode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UpdateOwnerCommissionSettingsAction
{
    public function execute(User $owner, CommissionMode $mode, ?float $rate): User
    {
        if (! $owner->isOwner()) {
            throw ValidationException::withMessages([
                'owner' => ['إعدادات العمولة تُضبط لمالكي المولدات فقط.'],
            ]);
        }

        if ($mode === CommissionMode::Fixed && $rate === null) {
            throw ValidationException::withMessages([
                'commission_rate' => ['نسبة العمولة مطلوبة عند اختيار الوضع الثابت.'],
            ]);
        }

        $owner->forceFill([
            'commission_mode' => $mode,
            'commission_rate' => $mode === CommissionMode::Fixed ? $rate : null,
        ])->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($owner)
            ->withProperties(['mode' => $mode->value, 'rate' => $rate])
            ->log('owner_commission_settings_updated');

        return $owner->fresh();
    }
}
