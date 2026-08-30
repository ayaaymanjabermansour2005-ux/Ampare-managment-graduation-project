<?php

namespace App\Services;

use App\Enums\CommissionMode;
use App\Enums\GeneratorStatus;
use App\Models\CommissionTier;
use App\Models\User;

class CommissionRateResolver
{
    public function resolve(User $owner): float
    {
        if ($owner->commission_mode === CommissionMode::Fixed && $owner->commission_rate !== null) {
            return (float) $owner->commission_rate;
        }

        if ($owner->commission_mode === CommissionMode::Tiered) {
            $generatorsCount = $owner->generators()->where('status', GeneratorStatus::Active->value)->count();

            $tier = CommissionTier::query()
                ->where('is_active', true)
                ->orderBy('min_generators_count')
                ->get()
                ->first(fn (CommissionTier $tier) => $tier->matches($generatorsCount));

            if ($tier) {
                return (float) $tier->commission_rate;
            }
        }

        return (float) config('billing.commission_rate', 10);
    }
}
