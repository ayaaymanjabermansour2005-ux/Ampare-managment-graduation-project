<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Enums\FaultPriority;
use App\Enums\FaultStatus;
use App\Enums\GeneratorStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\ServiceRequestStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianTaskStatus;
use App\Enums\TechnicianTaskType;
use App\Models\Complaint;
use App\Models\Fault;
use App\Models\FuelPurchase;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionServiceRequest;
use App\Models\Technician;
use App\Models\TechnicianTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AdminDashboardService
{
    private const TTL_SHORT = 300;   // 5 دقائق

    private const TTL_MEDIUM = 600;  // 10 دقائق

    private const TTL_ALERTS = 60;   // دقيقة واحدة

    public function __construct(private readonly FuelService $fuelService) {}

    public function stats(): array
    {
        return Cache::remember('admin.dashboard.stats_v2', self::TTL_SHORT, function () {
            return [
                'owners_count' => User::role(Role::GENERATOR_OWNER->value)->count(),
                'subscribers_count' => User::role(Role::SUBSCRIBER->value)->count(),
                'technicians_count' => Technician::count(),
                'subscriptions_count' => Subscription::count(),
                'active_subscriptions_count' => Subscription::where('status', SubscriptionStatus::Active->value)->count(),

                'generators_count' => Generator::count(),
                'active_generators_count' => Generator::where('status', GeneratorStatus::Active)->count(),
                'payments_pending_count' => Payment::where('status', PaymentStatus::Pending)->count(),
                'complaints_open_count' => Complaint::whereIn('status', [
                    ComplaintStatus::Pending->value,
                    ComplaintStatus::InProgress->value,
                ])->count(),

                'invoices_issued_count' => Invoice::count(),
                'invoices_paid_count' => Invoice::where('status', InvoiceStatus::Paid)->count(),
                'invoices_overdue_count' => Invoice::where('status', InvoiceStatus::Overdue)->count(),
                'total_revenue_ils' => (float) Invoice::where('status', InvoiceStatus::Paid)->sum('final_amount_ils'),

                'open_faults_count' => Fault::whereIn('status', [
                    FaultStatus::PendingVerification->value,
                    FaultStatus::Verified->value,
                    FaultStatus::InRepair->value,
                ])->count(),

                'maintenance_count' => TechnicianTask::whereIn('type', [
                    TechnicianTaskType::WiringMaintenance->value,
                    TechnicianTaskType::GeneralMaintenance->value,
                ])->whereNotIn('status', [
                    TechnicianTaskStatus::Approved->value,
                    TechnicianTaskStatus::Rejected->value,
                    TechnicianTaskStatus::Cancelled->value,
                ])->count(),

                'new_service_requests_count' => SubscriptionServiceRequest::where('status', ServiceRequestStatus::Pending->value)->count(),
            ];
        });
    }

    public function paymentsFinancialSummary(): array
    {
        return Cache::remember('admin.dashboard.payments_financial_summary', self::TTL_SHORT, function () {
            $todayStart = now()->startOfDay();
            $monthStart = now()->startOfMonth();

            return [
                'today_total_ils' => (float) Payment::where('status', PaymentStatus::Paid)
                    ->where('paid_at', '>=', $todayStart)
                    ->sum('amount_ils'),
                'month_total_ils' => (float) Payment::where('status', PaymentStatus::Paid)
                    ->where('paid_at', '>=', $monthStart)
                    ->sum('amount_ils'),
                'month_transactions_count' => Payment::where('status', PaymentStatus::Paid)
                    ->where('paid_at', '>=', $monthStart)
                    ->count(),
                'pending_transactions_count' => Payment::where('status', PaymentStatus::Pending)->count(),
            ];
        });
    }

    public function invoiceStatusBreakdown(): array
    {
        return Cache::remember('admin.dashboard.invoice_status_breakdown', self::TTL_SHORT, function () {
            $total = Invoice::count();

            if ($total === 0) {
                return ['paid' => 0, 'pending' => 0, 'overdue' => 0, 'total' => 0];
            }

            $paid = Invoice::where('status', InvoiceStatus::Paid)->count();
            $overdue = Invoice::where('status', InvoiceStatus::Overdue)->count();
            $pending = $total - $paid - $overdue;

            return [
                'paid' => $paid,
                'pending' => max(0, $pending),
                'overdue' => $overdue,
                'total' => $total,
            ];
        });
    }

    public function realtimeAlerts(int $limitPerType = 5): array
    {
        return Cache::remember("admin.dashboard.alerts.{$limitPerType}", self::TTL_ALERTS, function () use ($limitPerType) {
            $alerts = [];

            Generator::whereNotNull('tank_capacity_liters')
                ->with('latestFuelReading')
                ->limit(50)
                ->get(['id', 'name', 'tank_capacity_liters'])
                ->each(function ($g) use (&$alerts, $limitPerType) {
                    if (count(array_filter($alerts, fn ($a) => $a['type'] === 'fuel_low')) >= $limitPerType) {
                        return;
                    }

                    $status = $this->fuelService->checkLowFuelLevel($g, $g->latestFuelReading);

                    if ($status && $status['is_low']) {
                        $alerts[] = [
                            'type' => 'fuel_low',
                            'severity' => 'critical',
                            'title' => 'مستوى وقود منخفض',
                            'description' => "مولد {$g->name} ({$status['percentage']}%)",
                            'link_type' => 'generator',
                            'link_id' => $g->id,
                        ];
                    }
                });

            Fault::whereIn('status', [FaultStatus::PendingVerification->value, FaultStatus::Verified->value])
                ->where('priority', FaultPriority::Critical->value)
                ->with('generator:id,name')
                ->latest()
                ->limit($limitPerType)
                ->get()
                ->each(function ($f) use (&$alerts) {
                    $alerts[] = [
                        'type' => 'fault_critical',
                        'severity' => 'critical',
                        'title' => 'عطل حرج مفتوح',
                        'description' => $f->generator?->name ?? $f->title,
                        'link_type' => 'fault',
                        'link_id' => $f->id,
                    ];
                });

            Invoice::where('status', InvoiceStatus::Overdue)
                ->latest()
                ->limit($limitPerType)
                ->get(['id', 'final_amount_ils'])
                ->each(function ($inv) use (&$alerts) {
                    $alerts[] = [
                        'type' => 'invoice_overdue',
                        'severity' => 'warning',
                        'title' => 'فاتورة متأخرة السداد',
                        'description' => "فاتورة #{$inv->id} — {$inv->final_amount_ils} ₪",
                        'link_type' => 'invoice',
                        'link_id' => $inv->id,
                    ];
                });

            Complaint::where('status', ComplaintStatus::Pending->value)
                ->latest()
                ->limit($limitPerType)
                ->get(['id', 'subject'])
                ->each(function ($c) use (&$alerts) {
                    $alerts[] = [
                        'type' => 'complaint_open',
                        'severity' => 'info',
                        'title' => 'شكوى جديدة',
                        'description' => $c->subject,
                        'link_type' => 'complaint',
                        'link_id' => $c->id,
                    ];
                });

            return $alerts;
        });
    }

    /**
     * توزيع "الإيرادات مقابل المستحقات" — نفس منطق/نمط
     * UserService::ownersRevenueDistribution() المستخدَم بصفحة أصحاب
     * المولدات (فترة 6/12 شهر أو سنة محدَّدة بكل شهورها)، لتوحيد سلوك
     * قوائم فترة الإيرادات بين الداشبورد الرئيسية وصفحة أصحاب المولدات.
     */
    public function revenueVsOutstandingDistribution(?int $year = null, string $period = '6'): array
    {
        $cacheKey = 'admin.dashboard.revenue_vs_outstanding.'.($year ? "y{$year}" : "p{$period}");

        return Cache::remember($cacheKey, self::TTL_MEDIUM, function () use ($year, $period) {
            $labels = [];
            $revenue = [];
            $outstanding = [];

            $outstandingStatuses = [
                InvoiceStatus::Pending->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ];

            if ($year) {
                for ($month = 1; $month <= 12; $month++) {
                    $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
                    $periodEnd = $periodStart->copy()->endOfMonth();

                    $labels[] = $periodStart->translatedFormat('F');

                    $revenue[] = (float) Invoice::where('status', InvoiceStatus::Paid)
                        ->whereBetween('created_at', [$periodStart, $periodEnd])
                        ->sum('final_amount_ils');

                    $outstanding[] = (float) Invoice::whereIn('status', $outstandingStatuses)
                        ->whereBetween('created_at', [$periodStart, $periodEnd])
                        ->sum('final_amount_ils');
                }
            } else {
                $monthsCount = $period === '12' ? 12 : 6;

                for ($i = $monthsCount - 1; $i >= 0; $i--) {
                    $periodStart = now()->subMonths($i)->startOfMonth();
                    $periodEnd = now()->subMonths($i)->endOfMonth();

                    $labels[] = $monthsCount === 12
                        ? $periodStart->translatedFormat('F').' '.$periodStart->format('Y')
                        : $periodStart->translatedFormat('F');

                    $revenue[] = (float) Invoice::where('status', InvoiceStatus::Paid)
                        ->whereBetween('created_at', [$periodStart, $periodEnd])
                        ->sum('final_amount_ils');

                    $outstanding[] = (float) Invoice::whereIn('status', $outstandingStatuses)
                        ->whereBetween('created_at', [$periodStart, $periodEnd])
                        ->sum('final_amount_ils');
                }
            }

            return [
                'labels' => $labels,
                'revenue' => $revenue,
                'outstanding' => $outstanding,
                'available_years' => Invoice::query()
                    ->orderByDesc('created_at')
                    ->pluck('created_at')
                    ->map(fn ($date) => $date->year)
                    ->unique()
                    ->sortDesc()
                    ->values()
                    ->all(),
            ];
        });
    }

    public function subscriberGrowthByMonth(int $months = 6): array
    {
        return Cache::remember("admin.dashboard.subscriber_growth.{$months}", self::TTL_MEDIUM, function () use ($months) {
            $labels = [];
            $counts = [];

            for ($i = $months - 1; $i >= 0; $i--) {
                $periodStart = now()->subMonths($i)->startOfMonth();
                $periodEnd = now()->subMonths($i)->endOfMonth();

                $labels[] = $periodStart->translatedFormat('F');

                $counts[] = User::role(Role::SUBSCRIBER->value)
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->count();
            }

            return compact('labels', 'counts');
        });
    }

    public function fuelPurchasesLast7Days(): array
    {
        return Cache::remember('admin.dashboard.fuel_purchases_7d', self::TTL_SHORT, function () {
            $labels = [];
            $liters = [];

            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i);

                $labels[] = $day->translatedFormat('D');

                $liters[] = (float) FuelPurchase::whereDate('purchased_at', $day->toDateString())
                    ->sum('liters');
            }

            return compact('labels', 'liters');
        });
    }

    public function maintenanceAndFaultsByCity(int $limit = 5): array
    {
        return Cache::remember("admin.dashboard.maintenance_faults_by_city.{$limit}", self::TTL_MEDIUM, function () use ($limit) {
            $since = now()->subDays(30);

            $faultsByCity = Fault::query()
                ->whereHas('generator.location')
                ->where('created_at', '>=', $since)
                ->with('generator.location:id,city')
                ->get()
                ->groupBy(fn ($f) => $f->generator?->location?->city ?? 'غير محدد')
                ->map->count();

            $maintenanceByCity = TechnicianTask::query()
                ->whereIn('type', [
                    TechnicianTaskType::GeneralMaintenance->value,
                    TechnicianTaskType::WiringMaintenance->value,
                ])
                ->whereHas('generator.location')
                ->where('created_at', '>=', $since)
                ->with('generator.location:id,city')
                ->get()
                ->groupBy(fn ($t) => $t->generator?->location?->city ?? 'غير محدد')
                ->map->count();

            $cities = $faultsByCity->keys()
                ->merge($maintenanceByCity->keys())
                ->unique()
                ->sortByDesc(fn ($city) => ($faultsByCity[$city] ?? 0) + ($maintenanceByCity[$city] ?? 0))
                ->take($limit)
                ->values();

            return [
                'labels' => $cities->all(),
                'maintenance' => $cities->map(fn ($c) => $maintenanceByCity[$c] ?? 0)->all(),
                'faults' => $cities->map(fn ($c) => $faultsByCity[$c] ?? 0)->all(),
            ];
        });
    }

    public function generatorsMapPoints(): array
    {
        return Cache::remember('admin.dashboard.generators_map_v2', self::TTL_SHORT, function () {
            return Generator::query()
                ->whereHas('location', function ($q) {
                    $q->whereNotNull('latitude')->whereNotNull('longitude');
                })
                ->with('location:id,latitude,longitude,city')
                ->get(['id', 'name', 'name_en', 'status', 'location_id'])
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'name_en' => $g->name_en,
                    'status' => $g->status->value,
                    'lat' => (float) $g->location->latitude,
                    'lng' => (float) $g->location->longitude,
                    'city' => $g->location->city,
                ])
                ->values()
                ->all();
        });
    }

    public function clearCache(): void
    {
        $keys = [
            'admin.dashboard.stats',
            'admin.dashboard.payments_financial_summary',
            'admin.dashboard.invoice_status_breakdown',
            'admin.dashboard.alerts.5',
            'admin.dashboard.revenue_vs_outstanding.7',
            'admin.dashboard.subscriber_growth.6',
            'admin.dashboard.fuel_purchases_7d',
            'admin.dashboard.maintenance_faults_by_city.5',
            'admin.dashboard.generators_map',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
