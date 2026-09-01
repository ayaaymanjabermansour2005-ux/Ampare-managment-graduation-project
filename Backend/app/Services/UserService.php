<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianTaskStatus;
use App\Enums\UserStatus;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function list(
        ?string $roleFilter = null,
        ?string $search = null,
        int $perPage = 15,
        ?string $statusFilter = null,
        ?string $subscriptionStatus = null,
        ?int $generatorsCountMin = null,
        ?int $generatorsCountMax = null,
        ?float $commissionRateMin = null,
        ?float $commissionRateMax = null,
    ): LengthAwarePaginator {
        $query = User::query()->with('roles', 'permissions', 'plan');

        if ($roleFilter === 'generator_owner') {
            $query->withCount('generators')
                ->with(['generators' => function ($q) {
                    $q->withCount(['subscriptions as active_subscriptions_count' => fn ($sq) => $sq->where('status', SubscriptionStatus::Active->value)])
                        ->withSum(['invoices as monthly_revenue_ils' => function ($sq) {
                            $sq->where('invoices.status', InvoiceStatus::Paid->value)
                                ->whereMonth('invoices.created_at', now()->month)
                                ->whereYear('invoices.created_at', now()->year);
                        }], 'final_amount_ils');
                }])
                ->with('latestCommissionRate');

            if ($generatorsCountMin !== null) {
                $query->having('generators_count', '>=', $generatorsCountMin);
            }
            if ($generatorsCountMax !== null) {
                $query->having('generators_count', '<=', $generatorsCountMax);
            }
            if ($commissionRateMin !== null) {
                $query->where('commission_rate', '>=', $commissionRateMin);
            }
            if ($commissionRateMax !== null) {
                $query->where('commission_rate', '<=', $commissionRateMax);
            }
        }

        if ($roleFilter === 'subscriber') {
            $query->with([
                'subscriber.neighborhood',
                'subscriber.subscriptions.generator',
                'subscriber.subscriptions.invoices' => function ($q) {
                    $q->whereIn('invoices.status', [
                        InvoiceStatus::Pending->value,
                        InvoiceStatus::Overdue->value,
                        InvoiceStatus::PartiallyPaid->value,
                    ]);
                },
            ]);

            if ($subscriptionStatus === 'active') {
                $query->whereHas(
                    'subscriber.subscriptions',
                    fn ($q) => $q->where('subscriptions.status', SubscriptionStatus::Active)
                );
            } elseif ($subscriptionStatus === 'none') {
                $query->whereDoesntHave(
                    'subscriber.subscriptions',
                    fn ($q) => $q->where('subscriptions.status', SubscriptionStatus::Active)
                );
            } elseif ($subscriptionStatus === 'locked') {
                $query->whereNotNull('locked_until')->where('locked_until', '>', now());
            }
        }

        if ($roleFilter) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $roleFilter));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        return $query->latest()->paginate($perPage);
    }

    public function ownersStats(?int $revenueYear = null, string $revenuePeriod = '6'): array
    {
        $owners = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'generator_owner'))
            ->with('plan')
            ->get(['id', 'plan_id', 'locked_until']);

        $total = $owners->count();
        $locked = $owners->filter(fn ($u) => $u->locked_until && $u->locked_until->isFuture())->count();

        $planDistribution = $owners->groupBy(fn ($u) => $u->plan?->name ?? 'بدون خطة')
            ->map->count();

        $months = [];
        $counts = [];
        for ($i = 5; $i >= 0; $i--) {
            $periodStart = now()->subMonths($i)->startOfMonth();
            $periodEnd = now()->subMonths($i)->endOfMonth();
            $months[] = $periodStart->translatedFormat('F');
            $counts[] = User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', 'generator_owner'))
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count();
        }

        $revenueDistribution = $this->ownersRevenueDistribution($revenueYear, $revenuePeriod);
        $alerts = $this->ownersAlerts();
        $topOwners = $this->topOwnersByRevenue();

        $totalGenerators = (int) Generator::whereHas(
            'owner.roles',
            fn ($q) => $q->where('name', 'generator_owner')
        )->count();

        $totalRevenueThisMonth = (float) Invoice::where('status', InvoiceStatus::Paid->value)
            ->whereHas('subscription.generator.owner.roles', fn ($q) => $q->where('name', 'generator_owner'))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('final_amount_ils');

        $avgGeneratorsPerOwner = $total > 0 ? round($totalGenerators / $total, 1) : 0;

        return [
            'total' => $total,
            'locked' => $locked,
            'active' => $total - $locked,
            'total_generators' => $totalGenerators,
            'total_revenue_ils' => $totalRevenueThisMonth,
            'avg_generators_per_owner' => $avgGeneratorsPerOwner,
            'plan_distribution' => [
                'labels' => $planDistribution->keys()->values()->all(),
                'counts' => $planDistribution->values()->all(),
            ],
            'growth' => [
                'labels' => $months,
                'counts' => $counts,
            ],
            'revenue_distribution' => $revenueDistribution,
            'alerts' => $alerts,
            'top_owners_by_revenue' => $topOwners,
        ];
    }

    public function subscribersStats(): array
    {
        $subscribers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'subscriber'))
            ->with('subscriber')
            ->get(['id', 'name', 'status', 'locked_until']);

        $total = $subscribers->count();
        $locked = $subscribers->filter(fn ($u) => $u->locked_until && $u->locked_until->isFuture())->count();

        $beneficiaryDistribution = $subscribers
            ->groupBy(fn ($u) => $u->subscriber?->beneficiary_type?->label() ?? 'غير محدد')
            ->map->count();

        $months = [];
        $counts = [];
        for ($i = 5; $i >= 0; $i--) {
            $periodStart = now()->subMonths($i)->startOfMonth();
            $periodEnd = now()->subMonths($i)->endOfMonth();
            $months[] = $periodStart->translatedFormat('F');
            $counts[] = User::whereHas('roles', fn ($q) => $q->where('name', 'subscriber'))
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count();
        }

        $activeSubscriptionsCount = Subscription::where('status', SubscriptionStatus::Active)->count();
        $totalOutstanding = (float) Invoice::whereIn('status', [
            InvoiceStatus::Pending->value,
            InvoiceStatus::Overdue->value,
            InvoiceStatus::PartiallyPaid->value,
        ])->sum('final_amount_ils');

        $alerts = [];

        $overdueCount = User::whereHas('roles', fn ($q) => $q->where('name', 'subscriber'))
            ->whereHas('subscriber.subscriptions.invoices', fn ($q) => $q->where('invoices.status', InvoiceStatus::Overdue->value))
            ->count();
        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'overdue_balance',
                'severity' => 'critical',
                'title' => 'مستحقات متأخرة',
                'description' => "{$overdueCount} مشتركًا لديهم فواتير متأخرة السداد",
            ];
        }

        $pendingReviewCount = User::whereHas('roles', fn ($q) => $q->where('name', 'subscriber'))
            ->where('status', UserStatus::PendingReview->value)
            ->count();
        if ($pendingReviewCount > 0) {
            $alerts[] = [
                'type' => 'pending_review',
                'severity' => 'warning',
                'title' => 'طلبات تسجيل قيد المراجعة',
                'description' => "{$pendingReviewCount} مشتركين جدد ينتظرون الموافقة",
            ];
        }

        $suspendedCount = User::whereHas('roles', fn ($q) => $q->where('name', 'subscriber'))
            ->where('status', UserStatus::Suspended->value)
            ->count();

        $latestSuspended = User::whereHas('roles', fn ($q) => $q->where('name', 'subscriber'))
            ->where('status', UserStatus::Suspended->value)
            ->latest('updated_at')
            ->first();
        if ($latestSuspended) {
            $alerts[] = [
                'type' => 'suspended_account',
                'severity' => 'critical',
                'title' => 'حساب موقوف',
                'description' => "تم إيقاف حساب المشترك {$latestSuspended->name}",
            ];
        }

        $topDebtors = User::whereHas('roles', fn ($q) => $q->where('name', 'subscriber'))
            ->with([
                'subscriber.subscriptions.generator',
                'subscriber.subscriptions.invoices' => fn ($q) => $q->whereIn('invoices.status', [
                    InvoiceStatus::Pending->value,
                    InvoiceStatus::Overdue->value,
                    InvoiceStatus::PartiallyPaid->value,
                ]),
            ])
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'generators_count' => $u->subscriber?->subscriptions->pluck('generator_id')->unique()->count() ?? 0,
                'outstanding_balance_ils' => (float) ($u->subscriber?->subscriptions->flatMap->invoices->sum('final_amount_ils') ?? 0),
            ])
            ->sortByDesc('outstanding_balance_ils')
            ->take(5)
            ->values()
            ->all();

        return [
            'total' => $total,
            'locked' => $locked,
            'active' => $total - $locked,
            'overdue_count' => $overdueCount,
            'suspended_count' => $suspendedCount,
            'new_this_month_count' => end($counts) ?: 0,
            'active_subscriptions_count' => $activeSubscriptionsCount,
            'total_outstanding_ils' => $totalOutstanding,
            'beneficiary_distribution' => [
                'labels' => $beneficiaryDistribution->keys()->values()->all(),
                'counts' => $beneficiaryDistribution->values()->all(),
            ],
            'growth' => [
                'labels' => $months,
                'counts' => $counts,
            ],
            'alerts' => $alerts,
            'top_debtors' => $topDebtors,
        ];
    }

    public function ownersRevenueDistribution(?int $year = null, string $period = '6'): array
    {
        $labels = [];
        $revenue = [];
        $due = [];

        if ($year) {
            for ($month = 1; $month <= 12; $month++) {
                $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();
                $labels[] = $periodStart->translatedFormat('F');

                $revenue[] = (float) Invoice::where('status', InvoiceStatus::Paid->value)
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->sum('final_amount_ils');

                $due[] = (float) Invoice::whereIn('status', [
                    InvoiceStatus::Pending->value,
                    InvoiceStatus::PartiallyPaid->value,
                    InvoiceStatus::Overdue->value,
                ])
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

                $revenue[] = (float) Invoice::where('status', InvoiceStatus::Paid->value)
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->sum('final_amount_ils');

                $due[] = (float) Invoice::whereIn('status', [
                    InvoiceStatus::Pending->value,
                    InvoiceStatus::PartiallyPaid->value,
                    InvoiceStatus::Overdue->value,
                ])
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->sum('final_amount_ils');
            }
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'due' => $due,
            'available_years' => Invoice::query()
                ->orderByDesc('created_at')
                ->pluck('created_at')
                ->map(fn ($date) => $date->year)
                ->unique()
                ->sortDesc()
                ->values()
                ->all(),
        ];
    }

    private function ownersAlerts(): array
    {
        $alerts = [];

        $pendingDuesCount = User::whereHas('roles', fn ($q) => $q->where('name', 'generator_owner'))
            ->whereHas('generators.invoices', fn ($q) => $q->where('invoices.status', InvoiceStatus::Overdue->value))->count();

        if ($pendingDuesCount > 0) {
            $alerts[] = [
                'type' => 'pending_dues',
                'severity' => 'critical',
                'title' => 'مستحقات معلّقة',
                'description' => "{$pendingDuesCount} مالكًا لديهم مستحقات لم تُسوّ بعد",
            ];
        }

        $pendingReviewCount = User::whereHas('roles', fn ($q) => $q->where('name', 'generator_owner'))
            ->where('status', UserStatus::PendingReview->value)
            ->count();

        if ($pendingReviewCount > 0) {
            $alerts[] = [
                'type' => 'pending_review',
                'severity' => 'warning',
                'title' => 'طلبات انضمام قيد المراجعة',
                'description' => "{$pendingReviewCount} مالكين جدد ينتظرون الموافقة",
            ];
        }

        $latestSuspended = User::whereHas('roles', fn ($q) => $q->where('name', 'generator_owner'))
            ->where('status', UserStatus::Suspended->value)
            ->latest('updated_at')
            ->first();

        if ($latestSuspended) {
            $alerts[] = [
                'type' => 'suspended_account',
                'severity' => 'critical',
                'title' => 'حساب موقوف',
                'description' => "تم إيقاف حساب المالك {$latestSuspended->name}",
                'owner_id' => $latestSuspended->id,
            ];
        }

        return $alerts;
    }

    private function topOwnersByRevenue(int $limit = 5): array
    {
        $owners = User::whereHas('roles', fn ($q) => $q->where('name', 'generator_owner'))
            ->withCount('generators')
            ->with(['generators' => function ($q) {
                $q->withCount(['subscriptions as active_subscriptions_count' => fn ($sq) => $sq->where('status', SubscriptionStatus::Active->value)])
                    ->withSum(['invoices as monthly_revenue_ils' => function ($sq) {
                        $sq->where('invoices.status', InvoiceStatus::Paid->value)
                            ->whereMonth('invoices.created_at', now()->month)
                            ->whereYear('invoices.created_at', now()->year);
                    }], 'final_amount_ils');
            }])
            ->get();

        return $owners->map(fn ($o) => [
            'id' => $o->id,
            'name' => $o->name,
            'generators_count' => $o->generators_count,
            'subscribers_count' => $o->generators->sum('active_subscriptions_count'),
            'monthly_revenue_ils' => (float) $o->generators->sum('monthly_revenue_ils'),
        ])
            ->sortByDesc('monthly_revenue_ils')
            ->take($limit)
            ->values()
            ->all();
    }

    public function update(User $user, array $data): User
    {
        $emailChanged = array_key_exists('email', $data) && $data['email'] !== $user->email;

        $status = $data['status'] ?? null;
        unset($data['status']);

        $user->update($data);

        if ($status !== null) {
            $user->forceFill(['status' => $status])->save();
        }

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();
        }

        return $user->fresh(['roles', 'plan']) ?? $user;
    }

    public function updateAvatar(User $user, UploadedFile $file): User
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $file->store('avatars', 'public');

        $user->forceFill(['avatar_path' => $path])->save();

        return $user->fresh() ?? $user;
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->assertSafeToDelete($user);

            $user->delete();
        });
    }

    private function assertSafeToDelete(User $user): void
    {
        if ($user->isOwner()) {
            $activeSubscriptionsCount = Subscription::whereHas(
                'generator',
                fn ($q) => $q->where('owner_id', $user->id)
            )
                ->where('status', SubscriptionStatus::Active)
                ->count();

            if ($activeSubscriptionsCount > 0) {
                throw ValidationException::withMessages([
                    'user' => ["لا يمكن حذف هذا المستخدم لأنه مالك مولد مرتبط بـ {$activeSubscriptionsCount} اشتراك فعال. يجب إنهاء أو نقل هذه الاشتراكات أولاً."],
                ]);
            }
        }

        if ($user->isSubscriber() && $user->subscriber) {
            $activeSubscriptionsCount = $user->subscriber->subscriptions()
                ->where('subscriptions.status', SubscriptionStatus::Active)
                ->count();

            if ($activeSubscriptionsCount > 0) {
                throw ValidationException::withMessages([
                    'user' => ["لا يمكن حذف هذا المستخدم لأنه مرتبط بـ {$activeSubscriptionsCount} اشتراك فعال. يجب إنهاء هذه الاشتراكات أولاً."],
                ]);
            }
        }

        if ($user->isTechnician() && $user->technician) {
            $activeStatuses = array_map(
                fn (TechnicianTaskStatus $s) => $s->value,
                array_filter(TechnicianTaskStatus::cases(), fn ($s) => $s->isActive())
            );

            $activeTasksCount = $user->technician->tasks()
                ->whereIn('status', $activeStatuses)
                ->count();

            if ($activeTasksCount > 0) {
                throw ValidationException::withMessages([
                    'user' => ["لا يمكن حذف هذا المستخدم لأنه فني مرتبط بـ {$activeTasksCount} أمر شغل نشط. يجب إنهاء هذه المهام أولاً."],
                ]);
            }
        }
    }
}
