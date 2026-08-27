<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Fault;
use App\Models\FuelPurchase;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\PlatformCommission;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;

class OwnerMonthlyReportService
{
    public function build(User $owner, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $generatorIds = Generator::where('owner_id', $owner->id)->pluck('id');

        $revenue = Invoice::whereHas('subscription.generator', fn($q) => $q->where('owner_id', $owner->id))
            ->where('status', InvoiceStatus::Paid->value)
            ->whereBetween('created_at', [$start, $end])
            ->sum('final_amount_ils');

        $commission = PlatformCommission::where('owner_id', $owner->id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('commission_amount');

        $faultsCount = Fault::whereIn('generator_id', $generatorIds)
            ->whereBetween('reported_at', [$start, $end])
            ->count();

        $fuel = FuelPurchase::whereIn('generator_id', $generatorIds)
            ->whereBetween('purchased_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(liters),0) as total_liters, COALESCE(SUM(cost_amount_ils),0) as total_cost')
            ->first();

        return [
            'owner' => $owner,
            'period' => $start->translatedFormat('F Y'),
            'revenue_ils' => (float) $revenue,
            'commission_ils' => (float) $commission,
            'net_revenue_ils' => Money::sub((float) $revenue, (float) $commission, 2),
            'faults_count' => $faultsCount,
            'fuel_liters' => (float) $fuel->total_liters,
            'fuel_cost_ils' => (float) $fuel->total_cost,
            'generators_count' => $generatorIds->count(),
        ];
    }
}
