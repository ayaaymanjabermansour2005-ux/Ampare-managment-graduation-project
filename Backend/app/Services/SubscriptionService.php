<?php

namespace App\Services;

use App\Enums\GeneratorStatus;
use App\Enums\OperatingSchedule;
use App\Enums\SubscriberMeterStatus;
use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionApproved;
use App\Models\Generator;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    private const ALLOWED_TRANSITIONS = [
        SubscriptionStatus::Pending->value => [SubscriptionStatus::Active->value, SubscriptionStatus::Rejected->value],
        SubscriptionStatus::Active->value => [SubscriptionStatus::Suspended->value, SubscriptionStatus::Cancelled->value],
        SubscriptionStatus::Suspended->value => [SubscriptionStatus::Active->value, SubscriptionStatus::Cancelled->value],
        SubscriptionStatus::Cancelled->value => [],
        SubscriptionStatus::Rejected->value => [],
    ];

    public function __construct(
        protected GeneratorCapacityService $capacityService
    ) {}

    public function list(User $user, int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = Subscription::query()->with([
            'subscriberMeter.subscriber.user',
            'subscriberMeter.subscriber.neighborhood',
            'generator.owner',
        ]);
        if ($user->isAdmin()) {
        } elseif ($user->isSubscriber()) {
            $query->whereHas('subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $user->id));
        } elseif ($user->isOwner()) {
            $query->whereHas('generator', fn ($q) => $q->where('owner_id', $user->id));
        } elseif ($user->isTechnician()) {
            $query->whereHas('generator.technicians', fn ($q) => $q->where('technicians.user_id', $user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('subscriberMeter', fn ($meterQ) => $meterQ->where('meter_number', 'like', "%{$search}%"))
                    ->orWhereHas('subscriberMeter.subscriber.user', function ($userQ) use ($search) {
                        $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data, User $user): Subscription
    {
        $subscriber = $user->subscriber;

        if (! $subscriber) {
            throw ValidationException::withMessages([
                'subscriber' => ['حساب المشترك غير مكتمل، يرجى إكمال الملف الشخصي أولاً.'],
            ]);
        }

        $meter = $subscriber->meters()
            ->where('id', $data['subscriber_meter_id'])
            ->where('status', SubscriberMeterStatus::Active)
            ->first();

        if (! $meter) {
            throw ValidationException::withMessages([
                'subscriber_meter_id' => ['العداد المحدد غير موجود، غير فعّال، أو لا يخصك.'],
            ]);
        }

        return $this->createForMeter($data, $meter);
    }

    /**
     * إنشاء اشتراك من قِبَل مالك المولد نيابةً عن مشترك — العداد يكون قد
     * سبق التحقق من ملكيته للمشترك المستهدف ومن كونه فعّالًا في الـ FormRequest
     * (StoreOwnerSubscriptionRequest)، ونطاق ملكية المولد يتحقق منه الـ Policy
     * (SubscriptionPolicy::createByOwner) قبل الوصول لهذه الدالة.
     */
    public function createForOwner(array $data, User $owner, SubscriberMeter $meter): Subscription
    {
        return $this->createForMeter($data, $meter);
    }

    private function createForMeter(array $data, SubscriberMeter $meter): Subscription
    {
        $generator = Generator::findOrFail($data['generator_id']);

        if ($generator->status !== GeneratorStatus::Active) {
            throw ValidationException::withMessages([
                'generator_id' => ['هذا المولد غير متاح للاشتراك حالياً.'],
            ]);
        }

        $schedule = OperatingSchedule::from($data['schedule']);

        if (! $generator->canServeSchedule($schedule, $data['service_start_time'] ?? null, $data['service_end_time'] ?? null)) {
            throw ValidationException::withMessages([
                'generator_id' => ['فترة تشغيل هذا المولد لا تتوافق مع الفترة المطلوبة لهذا العقد.'],
            ]);
        }

        return DB::transaction(function () use ($data, $meter, $generator, $schedule) {
            $lockedGenerator = Generator::lockForUpdate()->findOrFail($generator->id);

            $this->capacityService->assertNoDuplicateContract(
                $lockedGenerator,
                $meter,
                $schedule,
                $data['service_start_time'] ?? null,
                $data['service_end_time'] ?? null
            );

            if (! empty($data['requested_capacity_kw'])) {
                $this->capacityService->assertCapacityAvailable(
                    $lockedGenerator,
                    $schedule,
                    $data['service_start_time'] ?? null,
                    $data['service_end_time'] ?? null,
                    (float) $data['requested_capacity_kw']
                );
            }

            $subscription = Subscription::create([
                'subscriber_meter_id' => $meter->id,
                'generator_id' => $lockedGenerator->id,
                'agreed_price_per_kw' => $lockedGenerator->price_per_kw,
                'currency' => $lockedGenerator->currency,
                'requested_capacity_kw' => $data['requested_capacity_kw'] ?? null,
                'schedule' => $schedule,
                'billing_cycle' => $data['billing_cycle'],
                'service_start_time' => $data['service_start_time'] ?? null,
                'service_end_time' => $data['service_end_time'] ?? null,
                'contract_type' => $data['contract_type'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * BUG-001: قبل هذا الإصلاح، كانت إعادة التحقق من السعة/التعارض تحصل فقط
     * عند الانتقال Suspended→Active — أبدًا عند Pending→Active، وهو مسار
     * الموافقة الإدارية الطبيعي. بما أن Subscription::CAPACITY_RESERVING_STATUSES
     * = ['active'] فقط (بتصميم صحيح ومتعمّد — اشتراك Pending ما بيحجز سعة بعد)،
     * كان ممكن لاشتراكين Pending ينجحا كلاهما بفحص السعة وقت الإنشاء (كل
     * واحد لحاله ما زال الثاني Pending)، وبعدين ينجحا كلاهما بالموافقة لأن
     * لحظة التفعيل الفعلية ما كانت تُعيد الفحص إطلاقًا — فيتجاوز إجمالي
     * الاشتراكات الفعّالة سعة المولد الحقيقية.
     *
     * الإصلاح: قفل صف الاشتراك أولًا (lockForUpdate) والتحقق من صلاحية
     * الانتقال باستخدام حالته الحقيقية بعد القفل (لا الحالة المحمَّلة قبل
     * دخول الـ transaction، تفاديًا لسباق منفصل: نفس الاشتراك يُعالَج مرتين
     * بالتوازي). بعدها، لأي انتقال فعلي إلى Active (سواء من Pending أو من
     * Suspended — الحالتان الوحيدتان المسموح بالوصول منهما لـ Active أصلًا
     * حسب ALLOWED_TRANSITIONS)، يُقفَل صف المولد (Generator::lockForUpdate)
     * وتُعاد نفس فحوصات التعارض/السعة الموجودة أصلًا لمسار Suspended→Active،
     * بدون أي تغيير بمنطقها — فقط تطبيقها الآن على كل مسار يصل إلى Active.
     */
    public function updateStatus(Subscription $subscription, string $status): Subscription
    {
        return DB::transaction(function () use ($subscription, $status) {
            $locked = Subscription::lockForUpdate()->findOrFail($subscription->id);
            $current = $locked->status->value;

            if (! in_array($status, self::ALLOWED_TRANSITIONS[$current] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => ["لا يمكن نقل الاشتراك من حالة \"{$current}\" إلى \"{$status}\" مباشرة."],
                ]);
            }

            if ($status === SubscriptionStatus::Active->value) {
                $locked->loadMissing('generator', 'subscriberMeter');

                if ($locked->generator?->capacity_kw !== null && $locked->requested_capacity_kw === null) {
                    throw ValidationException::withMessages([
                        'requested_capacity_kw' => ['لا يمكن الموافقة على هذا الاشتراك — المولد له حد سعة أقصى، ويجب أن يحدّد المشترك السعة المطلوبة أولًا. تواصل مع المشترك لطلب تعديل الطلب.'],
                    ]);
                }

                $lockedGenerator = Generator::lockForUpdate()->findOrFail($locked->generator_id);

                $this->capacityService->assertNoDuplicateContract(
                    $lockedGenerator,
                    $locked->subscriberMeter,
                    $locked->schedule,
                    $locked->service_start_time,
                    $locked->service_end_time,
                    excludeSubscriptionId: $locked->id
                );

                if ($locked->requested_capacity_kw) {
                    $this->capacityService->assertCapacityAvailable(
                        $lockedGenerator,
                        $locked->schedule,
                        $locked->service_start_time,
                        $locked->service_end_time,
                        (float) $locked->requested_capacity_kw,
                        excludeSubscriptionId: $locked->id
                    );
                }
            }

            $locked->forceFill(['status' => $status])->save();

            if ($status === SubscriptionStatus::Active->value) {
                SubscriptionApproved::dispatch($locked);
            }

            return $locked->fresh(['subscriberMeter.subscriber.neighborhood', 'subscriberMeter.subscriber.user', 'generator.owner']);
        });
    }
}
