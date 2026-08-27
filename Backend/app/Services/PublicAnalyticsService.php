<?php

namespace App\Services;

use App\Enums\FaultStatus;
use App\Enums\GeneratorStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\User;

class PublicAnalyticsService
{
    private const MONTH_KEYS = [1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr', 5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'aug', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec'];

    /**
     * @return array{
     *     quick_stats: array{today_revenue_ils: float, active_generators_count: int, open_faults_count: int, monthly_readings_count: int},
     *     revenue_growth: array{labels: array<int, string>, values: array<int, float>},
     *     subscriber_growth: array{labels: array<int, string>, values: array<int, int>},
     *     payment_methods: array<int, array{type: mixed, percentage: float|int}>,
     *     top_generators: array<int, array{name: mixed, name_en: mixed, city: mixed, subscribers_count: mixed}>,
     * }
     */
    public function summary(): array
    {
        return [
            'quick_stats' => $this->quickStats(),
            'revenue_growth' => $this->revenueGrowth(),
            'subscriber_growth' => $this->subscriberGrowth(),
            'payment_methods' => $this->paymentMethodsBreakdown(),
            'top_generators' => $this->topGenerators(),
        ];
    }

    /**
     * @return array{today_revenue_ils: float, active_generators_count: int, open_faults_count: int, monthly_readings_count: int}
     */
    private function quickStats(): array
    {
        return [
            'today_revenue_ils' => (float) Invoice::where('status', InvoiceStatus::Paid->value)
                ->whereDate('created_at', now()->toDateString())
                ->sum('final_amount_ils'),
            'active_generators_count' => Generator::where('status', GeneratorStatus::Active->value)->count(),
            'open_faults_count' => Fault::whereNotIn('status', [
                FaultStatus::Resolved->value,
                FaultStatus::Closed->value,
                FaultStatus::Rejected->value,
            ])->count(),
            'monthly_readings_count' => MeterReading::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function revenueGrowth(): array
    {
        $labels = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();
            $labels[] = self::MONTH_KEYS[(int) $month->format('n')];
            $values[] = (float) Invoice::where('status', InvoiceStatus::Paid->value)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->sum('final_amount_ils');
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function subscriberGrowth(): array
    {
        $labels = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();
            $labels[] = self::MONTH_KEYS[(int) $month->format('n')];
            $values[] = User::whereHas('roles', fn ($q) => $q->where('name', 'subscriber'))
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array<int, array{type: mixed, percentage: float|int}>
     */
    private function paymentMethodsBreakdown(): array
    {
        $rows = Payment::query()
            ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->where('payments.status', PaymentStatus::Paid->value)
            ->selectRaw('payment_methods.type as type, count(*) as cnt')
            ->groupBy('payment_methods.type')
            ->get();

        $total = $rows->sum('cnt');

        return $rows->map(fn ($r) => [
            'type' => $r->getAttribute('type'),
            'percentage' => $total > 0 ? round(($r->getAttribute('cnt') / $total) * 100) : 0,
        ])->values()->all();
    }

    /**
     * @return array<int, array{name: mixed, name_en: mixed, city: mixed, subscribers_count: mixed}>
     */
    private function topGenerators(int $limit = 3): array
    {
        return Generator::query()
            ->join('locations', 'generators.location_id', '=', 'locations.id')
            ->where('generators.status', GeneratorStatus::Active->value)
            ->withCount(['subscriptions as active_subscriptions_count' => fn ($q) => $q->where('status', SubscriptionStatus::Active->value)])
            ->orderByDesc('active_subscriptions_count')
            ->take($limit)
            ->get(['generators.id', 'generators.name', 'generators.name_en', 'locations.city'])
            ->map(fn ($g) => [
                'name' => $g->name,
                'name_en' => $g->name_en,
                'city' => $g->getAttribute('city'),
                'subscribers_count' => $g->getAttribute('active_subscriptions_count'),
            ])
            ->values()
            ->all();
    }
}
