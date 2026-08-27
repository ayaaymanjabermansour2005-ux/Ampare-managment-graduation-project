<?php

namespace App\Actions\Subscription;

use App\Models\Generator;
use App\Models\Subscription;
use App\Models\User;
use App\Services\GeneratorCapacityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransferSubscriptionAction
{
    public function __construct(
        private readonly GeneratorCapacityService $capacityService
    ) {}

    /**
     * @param  User  $actor  المستخدم الذي نفَّذ النقل (يُسجَّل في activity log).
     * @param  User|null  $ownerScope  عند تمريره (مسار مالك المولد)، يفرض أن كلا
     *                                 المولدين — الحالي (المصدر) والجديد (الهدف) —
     *                                 مملوكان لهذا المستخدم تحديدًا. يتم التحقق
     *                                 داخل نفس الـ transaction بعد lockForUpdate
     *                                 على كليهما لتفادي أي تلاعب زمني (TOCTOU).
     *                                 null (المسار الإداري الحالي) يعني بلا قيود
     *                                 على المولد الهدف، تمامًا كالسلوك الحالي.
     */
    public function execute(Subscription $subscription, Generator $newGenerator, User $actor, ?User $ownerScope = null): Subscription
    {
        return DB::transaction(function () use ($subscription, $newGenerator, $actor, $ownerScope) {
            $subscription = Subscription::lockForUpdate()->findOrFail($subscription->id);
            $newGenerator = Generator::lockForUpdate()->findOrFail($newGenerator->id);

            if ($ownerScope) {
                $subscription->loadMissing('generator');

                if ($subscription->generator?->owner_id !== $ownerScope->id || $newGenerator->owner_id !== $ownerScope->id) {
                    throw ValidationException::withMessages([
                        'generator_id' => ['لا يمكنك نقل هذا الاشتراك — يجب أن يكون المولد الحالي والمولد الجديد كلاهما ضمن مولداتك.'],
                    ]);
                }
            }

            if (! $newGenerator->canServeSchedule($subscription->schedule, $subscription->service_start_time, $subscription->service_end_time)) {
                throw ValidationException::withMessages([
                    'generator_id' => ['فترة تشغيل المولد الجديد لا تتوافق مع فترة هذا الاشتراك.'],
                ]);
            }

            $subscription->loadMissing('subscriberMeter');

            $this->capacityService->assertNoDuplicateContract(
                $newGenerator,
                $subscription->subscriberMeter,
                $subscription->schedule,
                $subscription->service_start_time,
                $subscription->service_end_time,
                excludeSubscriptionId: $subscription->id
            );

            if ($subscription->requested_capacity_kw) {
                $this->capacityService->assertCapacityAvailable(
                    $newGenerator,
                    $subscription->schedule,
                    $subscription->service_start_time,
                    $subscription->service_end_time,
                    (float) $subscription->requested_capacity_kw,
                    excludeSubscriptionId: $subscription->id
                );
            }

            $oldGeneratorId = $subscription->generator_id;
            $subscription->update(['generator_id' => $newGenerator->id]);

            activity()
                ->causedBy($actor)
                ->performedOn($subscription)
                ->withProperties(['from_generator_id' => $oldGeneratorId, 'to_generator_id' => $newGenerator->id])
                ->log($ownerScope ? 'owner_transferred_subscription' : 'admin_transferred_subscription');

            return $subscription->fresh(['generator.owner', 'subscriberMeter.subscriber.user']);
        });
    }
}
