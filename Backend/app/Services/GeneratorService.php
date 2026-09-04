<?php

namespace App\Services;

use App\Enums\GeneratorStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OperatingSchedule;
use App\Enums\SubscriptionStatus;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\GeneratorRejectedNotification;
use App\Notifications\GeneratorVerifiedNotification;
use App\Support\Eloquent\FreshOrFail;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GeneratorService
{
    public function __construct(
        protected GeneratorCapacityService $capacityService
    ) {}

    public function list(
        User $user,
        int $perPage = 15,
        ?string $search = null,
        ?string $status = null,
        ?string $city = null,
        ?int $ownerId = null,
        ?float $capacityMin = null,
        ?float $capacityMax = null,
        ?float $fuelMin = null,
        ?float $fuelMax = null,
        ?int $subscribersMin = null,
        ?int $subscribersMax = null,
        ?float $revenueMin = null,
        ?float $revenueMax = null,
    ): LengthAwarePaginator {
        $query = Generator::query()
            ->withCount([
                'subscriptions' => fn ($q) => $q->where('status', SubscriptionStatus::Active),
            ])
            ->withSum(['invoices as monthly_revenue_ils' => function ($q) {
                $q->where('invoices.status', InvoiceStatus::Paid->value)
                    ->whereMonth('invoices.created_at', now()->month)
                    ->whereYear('invoices.created_at', now()->year);
            }], 'final_amount_ils')
            ->with([
                'owner',
                'location.neighborhood',
                'latestFuelReading',
                'latestMaintenanceTask',
            ]);

        if ($user->isAdmin()) {
            // فلتر اختياري حسب المالك — للأدمن فقط (Owner/Technician أصلًا
            // مقيّدون ببياناتهم الخاصة عبر الشرط أدناه).
            if ($ownerId) {
                $query->where('owner_id', $ownerId);
            }
        } elseif ($user->isOwner()) {
            $query->where('owner_id', $user->id);
        } elseif ($user->isTechnician()) {
            $query->whereHas(
                'technicians',
                fn ($q) => $q->where('technicians.user_id', $user->id)
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('location', fn ($locQ) => $locQ->where('city', 'like', "%{$search}%"))
                    ->orWhereHas('owner', fn ($ownerQ) => $ownerQ->where('name', 'like', "%{$search}%"));
            });
        }
        if ($status) {
            $query->where('status', $status);
        }

        if ($city) {
            $query->whereHas('location', fn ($q) => $q->where('city', $city));
        }

        if ($capacityMin !== null) {
            $query->where('capacity_kw', '>=', $capacityMin);
        }
        if ($capacityMax !== null) {
            $query->where('capacity_kw', '<=', $capacityMax);
        }

        if ($fuelMin !== null || $fuelMax !== null) {
            $percentExpr = '(SELECT (fr.tank_level_liters / NULLIF(generators.tank_capacity_liters, 0)) * 100
                FROM fuel_readings fr
                WHERE fr.generator_id = generators.id AND fr.deleted_at IS NULL
                ORDER BY fr.reading_date DESC LIMIT 1)';
            if ($fuelMin !== null) {
                $query->whereRaw("{$percentExpr} >= ?", [$fuelMin]);
            }
            if ($fuelMax !== null) {
                $query->whereRaw("{$percentExpr} <= ?", [$fuelMax]);
            }
        }

        if ($subscribersMin !== null) {
            $query->having('subscriptions_count', '>=', $subscribersMin);
        }
        if ($subscribersMax !== null) {
            $query->having('subscriptions_count', '<=', $subscribersMax);
        }

        if ($revenueMin !== null) {
            $query->having('monthly_revenue_ils', '>=', $revenueMin);
        }
        if ($revenueMax !== null) {
            $query->having('monthly_revenue_ils', '<=', $revenueMax);
        }

        return $query->latest()->paginate($perPage);
    }

    public function stats(User $user): array
    {
        $applyScope = function ($query) use ($user) {
            if ($user->isAdmin()) {
                return $query;
            }
            if ($user->isOwner()) {
                return $query->where('owner_id', $user->id);
            }
            if ($user->isTechnician()) {
                return $query->whereHas('technicians', fn ($q) => $q->where('technicians.user_id', $user->id));
            }

            return $query->whereRaw('1 = 0');
        };

        $base = $applyScope(Generator::query());

        $statusCounts = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalGenerators = (int) $statusCounts->sum();
        $totalCapacity = (int) (clone $base)->sum('capacity_kw');

        $fuelRows = (clone $base)
            ->whereNotNull('tank_capacity_liters')
            ->with('latestFuelReading:id,fuel_readings.generator_id,tank_level_liters')
            ->get(['id', 'tank_capacity_liters']);

        $fuelPercentages = $fuelRows
            ->filter(fn ($g) => $g->latestFuelReading)
            ->map(fn ($g) => ((float) $g->latestFuelReading->tank_level_liters / (float) $g->tank_capacity_liters) * 100);

        $totalSubscribers = Subscription::where('status', SubscriptionStatus::Active->value)
            ->whereHas('generator', fn ($q) => $applyScope($q))
            ->count();

        $totalMonthlyRevenue = Invoice::where('status', InvoiceStatus::Paid->value)
            ->whereMonth('invoices.created_at', now()->month)
            ->whereYear('invoices.created_at', now()->year)
            ->whereHas('subscription.generator', fn ($q) => $applyScope($q))
            ->sum('final_amount_ils');

        return [
            'total' => $totalGenerators,
            'total_capacity_kw' => $totalCapacity,
            'active' => (int) ($statusCounts['active'] ?? 0),
            'maintenance' => (int) ($statusCounts['maintenance'] ?? 0),
            'inactive' => (int) ($statusCounts['inactive'] ?? 0),
            'pending_verification' => (int) ($statusCounts['pending_verification'] ?? 0),
            'rejected' => (int) ($statusCounts['rejected'] ?? 0),
            'avg_fuel_percentage' => $fuelPercentages->isNotEmpty() ? round($fuelPercentages->avg(), 1) : null,
            'total_subscribers' => $totalSubscribers,
            'total_monthly_revenue_ils' => (float) $totalMonthlyRevenue,
        ];
    }

    /**
     * قائمة المدن الفريدة لكل المولدات المسموح للمستخدم رؤيتها — لفلتر المنطقة.
     * تُبنى عبر Eloquent Collections (لا Raw JOIN) تفاديًا لتعارض أسماء الأعمدة.
     */
    public function distinctCities(User $user): array
    {
        $query = Generator::query();

        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->where('owner_id', $user->id);
        } elseif ($user->isTechnician()) {
            $query->whereHas('technicians', fn ($q) => $q->where('technicians.user_id', $user->id));
        } else {
            return [];
        }

        return $query->whereHas('location')
            ->with('location:id,city')
            ->get(['id', 'location_id'])
            ->pluck('location.city')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function create(array $data, User $user): Generator
    {
        $data['owner_id'] = $data['owner_id'] ?? $user->id;
        $ownerId = (int) $data['owner_id'];

        $data['status'] = $user->isAdmin()
            ? GeneratorStatus::Active->value
            : GeneratorStatus::PendingVerification->value;

        return DB::transaction(function () use ($data, $ownerId) {
            $owner = User::lockForUpdate()->findOrFail($ownerId);

            $this->validateGeneratorLimit($owner);
            $this->resolveLocationFromCoordinates($data, null);

            $generator = Generator::create($data);

            return FreshOrFail::reload($generator, ['owner', 'location.neighborhood']);
        });
    }

    public function verify(Generator $generator, User $admin): Generator
    {
        return DB::transaction(function () use ($generator, $admin) {
            $generator = Generator::lockForUpdate()->findOrFail($generator->id);

            if ($generator->status !== GeneratorStatus::PendingVerification) {
                throw ValidationException::withMessages([
                    'generator' => ['هذا المولد ليس بانتظار الاعتماد.'],
                ]);
            }

            $generator->forceFill([
                'status' => GeneratorStatus::Active,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ])->save();

            $fresh = FreshOrFail::reload($generator, ['owner', 'location.neighborhood']);

            if (NotificationPreferenceGate::allows($fresh->owner, 'notify_generator_verified')) {
                $fresh->owner->notify(new GeneratorVerifiedNotification($fresh));
            }

            return $fresh;
        });
    }

    public function reject(Generator $generator, User $admin, string $reason): Generator
    {
        return DB::transaction(function () use ($generator, $admin, $reason) {
            $generator = Generator::lockForUpdate()->findOrFail($generator->id);

            if ($generator->status !== GeneratorStatus::PendingVerification) {
                throw ValidationException::withMessages([
                    'generator' => ['هذا المولد ليس بانتظار الاعتماد.'],
                ]);
            }

            $generator->forceFill([
                'status' => GeneratorStatus::Rejected,
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $fresh = FreshOrFail::reload($generator, ['owner', 'location.neighborhood']);

            if (NotificationPreferenceGate::allows($fresh->owner, 'notify_generator_rejected')) {
                $fresh->owner->notify(new GeneratorRejectedNotification($fresh));
            }

            return $fresh;
        });
    }

    private const OWNER_EDITABLE_STATUSES = [
        GeneratorStatus::Active,
        GeneratorStatus::Inactive,
        GeneratorStatus::Maintenance,
    ];

    public function update(
        Generator $generator,
        array $data,
        ?User $actingUser = null
    ): Generator {
        return DB::transaction(function () use ($generator, $data, $actingUser) {
            $generator = Generator::lockForUpdate()->findOrFail($generator->id);

            if (isset($data['status'])) {
                $this->assertStatusTransitionAllowed($generator, GeneratorStatus::from($data['status']), $actingUser);
            }

            $isDeactivating = isset($data['status'])
                && GeneratorStatus::from($data['status']) !== GeneratorStatus::Active
                && $generator->status === GeneratorStatus::Active;

            if ($isDeactivating) {
                $this->assertNoActiveSubscriptions($generator, 'تعطيل');
            }

            unset($data['owner_id']);
            $this->resolveLocationFromCoordinates($data, $generator);

            $generator->update($data);

            return FreshOrFail::reload($generator, [
                'owner',
                'location.neighborhood',
            ]);
        });
    }

    /**
     * Only an admin may move a generator into/out of the verification states
     * (pending_verification, rejected) or transition it out of them — that
     * workflow belongs exclusively to GeneratorController::verify/reject.
     * A non-admin owner may only toggle between the operational statuses
     * (active/inactive/maintenance) of an already-verified generator.
     */
    private function assertStatusTransitionAllowed(Generator $generator, GeneratorStatus $newStatus, ?User $actingUser): void
    {
        if ($actingUser !== null && $actingUser->isAdmin()) {
            return;
        }

        $verificationStates = [GeneratorStatus::PendingVerification, GeneratorStatus::Rejected];

        if (
            in_array($generator->status, $verificationStates, true)
            || in_array($newStatus, $verificationStates, true)
            || ! in_array($newStatus, self::OWNER_EDITABLE_STATUSES, true)
        ) {
            throw ValidationException::withMessages([
                'status' => ['لا يمكنك تغيير حالة اعتماد المولد. يتم اعتماد أو رفض المولد من قبل الإدارة فقط.'],
            ]);
        }
    }

    public function delete(Generator $generator): void
    {
        DB::transaction(function () use ($generator) {
            $generator = Generator::lockForUpdate()->findOrFail($generator->id);

            $this->assertNoActiveSubscriptions($generator, 'حذف');

            $generator->delete();
        });
    }

    private function assertNoActiveSubscriptions(Generator $generator, string $action): void
    {
        $activeCount = $generator
            ->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->count();

        if ($activeCount > 0) {
            throw ValidationException::withMessages([
                'generator' => [
                    "لا يمكن {$action} هذا المولد لأنه مرتبط بـ {$activeCount} اشتراك فعال. يجب إنهاء أو نقل هذه الاشتراكات أولاً.",
                ],
            ]);
        }
    }

    public function available(
        User $user,
        ?int $subscriberMeterId = null,
        ?string $schedule = null,
        ?string $serviceStartTime = null,
        ?string $serviceEndTime = null,
        ?float $requestedCapacityKw = null
    ): LengthAwarePaginator|Collection {

        $query = Generator::query()
            ->where(
                'status',
                GeneratorStatus::Active
            )
            ->with([
                'owner',
                'location.neighborhood',
            ]);

        $scheduleEnum = $schedule
            ? OperatingSchedule::tryFrom($schedule)
            : null;

        if (
            ! $subscriberMeterId ||
            ! $scheduleEnum ||
            $requestedCapacityKw === null
        ) {
            return $query
                ->latest()
                ->paginate(15);
        }

        $meter = SubscriberMeter::query()
            ->where('id', $subscriberMeterId)
            ->where(
                'subscriber_id',
                $user->subscriber?->id
            )
            ->first();

        if (! $meter) {
            return Generator::query()
                ->whereRaw('1 = 0')
                ->paginate(15);
        }

        $generators = $query
            ->latest()
            ->limit(200)
            ->get();

        return $generators
            ->filter(
                fn (Generator $generator) => $this->capacityService->canAccept(
                    $generator,
                    $meter,
                    $scheduleEnum,
                    $serviceStartTime,
                    $serviceEndTime,
                    $requestedCapacityKw
                )
            )
            ->values();
    }

    private function resolveLocationFromCoordinates(array &$data, ?Generator $existing): void
    {
        $hasLatitude = array_key_exists('latitude', $data);
        $hasLongitude = array_key_exists('longitude', $data);

        if (! $hasLatitude && ! $hasLongitude) {
            return;
        }

        $latitude = $hasLatitude ? $data['latitude'] : null;
        $longitude = $hasLongitude ? $data['longitude'] : null;
        // BUG-004: always strip these keys once either is present, even when
        // only one of the two was sent — `latitude`/`longitude` are not
        // fillable on Generator, so leaving a lone key in $data crashed
        // create()/update() with a MassAssignmentException instead of a
        // clean no-op.
        unset($data['latitude'], $data['longitude']);

        if ($latitude === null || $longitude === null) {
            return;
        }

        $existingLocationId = $existing?->location_id ?? ($data['location_id'] ?? null);

        if ($existingLocationId) {
            Location::where('id', $existingLocationId)->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
            $data['location_id'] = $existingLocationId;

            return;
        }

        $location = Location::create([
            'city' => 'غزة',
            'neighborhood_id' => null,
            'address' => null,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        $data['location_id'] = $location->id;
    }

    private function validateGeneratorLimit(User $owner): void
    {
        if (
            ! $owner->plan ||
            $owner->plan->max_generators === null
        ) {
            return;
        }

        $currentCount = Generator::query()
            ->where(
                'owner_id',
                $owner->id
            )
            ->count();

        if (
            $currentCount >=
            $owner->plan->max_generators
        ) {
            throw ValidationException::withMessages([
                'plan' => [
                    "وصلت للحد الأقصى لعدد المولدات المسموح بخطتك الحالية ({$owner->plan->name} — {$owner->plan->max_generators}). يرجى ترقية الخطة.",
                ],
            ]);
        }
    }

    /**
     * نقاط خفيفة لعرض المولدات على الخريطة (id/name/status/lat/lng) — بدون
     * أي حقول ثقيلة (owner, invoices, fuel...) لتفادي جلب GeneratorResource
     * الكامل فقط لاستخراج الإحداثيات. نفس نطاق الصلاحيات المستخدَم في list().
     */
    public function mapPoints(User $user): array
    {
        $query = Generator::query()
            ->whereHas('location', function ($q) {
                $q->whereNotNull('latitude')->whereNotNull('longitude');
            })
            ->with('location:id,latitude,longitude,city');

        if ($user->isAdmin()) {
            // بلا فلترة — كل المولدات
        } elseif ($user->isOwner()) {
            $query->where('owner_id', $user->id);
        } elseif ($user->isTechnician()) {
            $query->whereHas(
                'technicians',
                fn ($q) => $q->where('technicians.user_id', $user->id)
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
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
    }
}
