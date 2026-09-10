<?php

namespace App\Services;

use App\Enums\MeterReadingStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Generator;
use App\Models\MeterReading;
use App\Models\Subscription;
use App\Models\Technician;
use App\Models\User;
use App\Support\Eloquent\FreshOrFail;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MeterReadingService
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function list(User $user, int $perPage = 15, ?string $search = null, ?string $status = null, ?int $technicianId = null): LengthAwarePaginator
    {
        // FIX (تدقيق شامل — B4): approver لم يكن يُحمَّل مسبقًا، فحقل "تمت
        // الموافقة من" (approved_by) لم يكن يظهر أبدًا رغم وجود عنصر واجهة له.
        $query = MeterReading::query()->with(['subscription.generator', 'subscription.subscriberMeter.subscriber.user', 'creator', 'approver']);

        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->whereHas('subscription.generator', fn ($q) => $q->where('owner_id', $user->id));
        } elseif ($user->isSubscriber()) {
            $query->whereHas('subscription.subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $user->id));
        } elseif ($user->isTechnician()) {
            $query->whereHas('subscription.generator.technicians', fn ($q) => $q->where('technicians.user_id', $user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        // Owner-facing "Technician Portal" monitoring: narrow an already
        // owner-scoped query down to one technician's own submitted readings.
        // Only meaningful (and only ever applied) for Owner/Admin callers —
        // the base scope above already prevents cross-owner leakage.
        if ($technicianId) {
            $technicianUserId = Technician::whereKey($technicianId)->value('user_id');
            $query->where('created_by', $technicianUserId);
        }

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas(
                    'subscription.subscriberMeter.subscriber.user',
                    fn ($u) => $u->where('name', 'like', "%{$search}%")
                )->orWhereHas(
                    'subscription.generator',
                    fn ($g) => $g->where('name', 'like', "%{$search}%")
                );
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest('reading_date')->paginate($perPage);
    }

    public function create(array $data, User $user): MeterReading
    {
        return DB::transaction(function () use ($data, $user) {
            $subscription = Subscription::with(['generator', 'subscriberMeter.subscriber'])
                ->lockForUpdate()
                ->findOrFail($data['subscription_id']);

            if ($subscription->status->value !== 'active') {
                throw ValidationException::withMessages([
                    'subscription_id' => ['لا يمكن تسجيل قراءة لاشتراك غير فعّال حالياً.'],
                ]);
            }

            $duplicateExists = MeterReading::where('subscription_id', $subscription->id)
                ->where('reading_date', $data['reading_date'])
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'reading_date' => ['تم تسجيل قراءة لهذا الاشتراك بنفس هذا التاريخ مسبقًا.'],
                ]);
            }

            $lastReading = MeterReading::where('subscription_id', $subscription->id)
                ->latest('reading_date')
                ->first();

            $previousReading = $lastReading?->current_reading ?? 0;

            if ($data['current_reading'] < $previousReading) {
                throw ValidationException::withMessages([
                    'current_reading' => ['القراءة الحالية لا يمكن أن تكون أقل من القراءة السابقة ('.$previousReading.').'],
                ]);
            }

            $readingDate = Carbon::parse($data['reading_date']);
            $baseDate = $lastReading
                ? Carbon::parse($lastReading->reading_date)
                : ($subscription->start_date ? Carbon::parse($subscription->start_date) : null);

            $dueDate = $baseDate?->copy()->addDays($subscription->billing_cycle->intervalDays());
            $isEarly = $dueDate !== null && $readingDate->lt($dueDate);

            $isAutoApproved = $user->isAdmin() || $user->isOwner();

            $meterReading = MeterReading::create([
                'subscription_id' => $subscription->id,
                'reading_date' => $data['reading_date'],
                'previous_reading' => $previousReading,
                'current_reading' => $data['current_reading'],
                'created_by' => $user->id,
            ]);

            $meterReading->forceFill([
                'status' => $isAutoApproved ? MeterReadingStatus::Approved : MeterReadingStatus::PendingApproval,
                'approved_by' => $isAutoApproved ? $user->id : null,
                'approved_at' => $isAutoApproved ? now() : null,
            ])->save();

            if ($isAutoApproved) {
                $this->invoiceService->createFromMeterReading(FreshOrFail::reload($meterReading), $subscription);
            }

            $fresh = FreshOrFail::reload($meterReading, ['subscription.generator', 'subscription.subscriberMeter.subscriber.user', 'invoice', 'approver']);

            if ($isEarly) {
                $daysEarly = $readingDate->diffInDays($dueDate);
                $fresh->reading_warning = "هذه القراءة قبل موعد الفوترة المتوقع بـ {$daysEarly} يوم (الموعد المتوقع: {$dueDate->toDateString()}).";
            }

            return $fresh;
        });
    }

    public function update(MeterReading $meterReading, array $data): MeterReading
    {
        return DB::transaction(function () use ($meterReading, $data) {
            $meterReading = MeterReading::lockForUpdate()->findOrFail($meterReading->id);

            if ($meterReading->status !== MeterReadingStatus::PendingApproval) {
                throw ValidationException::withMessages([
                    'status' => ['لا يمكن تعديل قراءة معتمدة أو مرفوضة.'],
                ]);
            }

            $meterReading->update([
                'current_reading' => $data['current_reading'],
            ]);

            return FreshOrFail::reload($meterReading, ['subscription.generator', 'subscription.subscriberMeter.subscriber.user', 'creator']);
        });
    }

    public function delete(MeterReading $meterReading): void
    {
        DB::transaction(function () use ($meterReading) {
            $meterReading = MeterReading::lockForUpdate()->findOrFail($meterReading->id);

            if ($meterReading->status !== MeterReadingStatus::PendingApproval) {
                throw ValidationException::withMessages([
                    'status' => ['لا يمكن حذف قراءة معتمدة أو مرفوضة.'],
                ]);
            }

            $meterReading->delete();
        });
    }

    public function approve(MeterReading $meterReading, User $approver): MeterReading
    {
        return DB::transaction(function () use ($meterReading, $approver) {
            $meterReading = MeterReading::lockForUpdate()->findOrFail($meterReading->id);

            if ($meterReading->status !== MeterReadingStatus::PendingApproval) {
                throw ValidationException::withMessages([
                    'status' => ['هذه القراءة معتمدة أصلاً أو ليست بانتظار الاعتماد.'],
                ]);
            }

            $meterReading->forceFill([
                'status' => MeterReadingStatus::Approved,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ])->save();

            $this->invoiceService->createFromMeterReading(
                FreshOrFail::reload($meterReading),
                $meterReading->subscription
            );

            return FreshOrFail::reload($meterReading, ['subscription.generator', 'subscription.subscriberMeter.subscriber.user', 'invoice', 'approver']);
        });
    }

    public function reject(MeterReading $meterReading, User $rejecter, string $reason): MeterReading
    {
        return DB::transaction(function () use ($meterReading, $rejecter, $reason) {
            $meterReading = MeterReading::lockForUpdate()->findOrFail($meterReading->id);

            if ($meterReading->status !== MeterReadingStatus::PendingApproval) {
                throw ValidationException::withMessages([
                    'status' => ['هذه القراءة معتمدة أصلاً أو ليست بانتظار الاعتماد.'],
                ]);
            }

            $meterReading->forceFill([
                'status' => MeterReadingStatus::Rejected,
                'approved_by' => $rejecter->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return FreshOrFail::reload($meterReading, ['subscription.generator', 'subscription.subscriberMeter.subscriber.user', 'creator', 'approver']);
        });
    }

    /**
     * الاشتراكات الفعّالة المتأخرة عن موعد تسجيل قراءة جديدة، ضمن نطاق صلاحية المستخدم.
     */
    public function overdueSubscribers(User $user): Collection
    {
        $query = Subscription::with(['subscriberMeter.subscriber.user', 'generator', 'meterReadings'])
            ->where('status', SubscriptionStatus::Active->value);

        if ($user->isAdmin()) {
            // بدون تقييد إضافي.
        } elseif ($user->isOwner()) {
            $generatorIds = Generator::where('owner_id', $user->id)->pluck('id');
            $query->whereIn('generator_id', $generatorIds);
        } elseif ($user->isTechnician()) {
            $query->whereHas('generator.technicians', fn ($q) => $q->where('technicians.user_id', $user->id));
        } else {
            return collect();
        }

        return $query->get()
            ->filter(fn (Subscription $subscription) => $subscription->isDueForReading())
            ->map(fn (Subscription $subscription) => [
                'id' => $subscription->id,
                'name' => $subscription->subscriberMeter?->subscriber?->user?->name,
                'generator_name' => $subscription->generator?->name,
            ])
            ->values();
    }

    /**
     * سجل استهلاك آخر N قراءة معتمدة لاشتراك معيّن (للرسم البياني).
     */
    public function history(Subscription $subscription, int $limit = 6): Collection
    {
        return $subscription->meterReadings()
            ->where('status', MeterReadingStatus::Approved->value)
            ->latest('reading_date')
            ->take($limit)
            ->get(['reading_date', 'consumed_kw'])
            ->sortBy('reading_date')
            ->values()
            ->map(fn (MeterReading $reading) => [
                'month' => $reading->reading_date?->format('Y-m'),
                'consumed_kw' => (float) $reading->consumed_kw,
            ]);
    }
}
