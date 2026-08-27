<?php

namespace App\Actions\SubscriptionMeterTransfer;

use App\Enums\SubscriberMeterStatus;
use App\Enums\SubscriptionMeterTransferStatus;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\SubscriptionMeterTransferRequest;
use App\Models\User;
use App\Services\GeneratorCapacityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApproveSubscriptionMeterTransferRequestAction
{
    public function __construct(protected GeneratorCapacityService $capacityService) {}

    /**
     * الموافقة على طلب نقل عداد وتنفيذ النقل الفعلي على الاشتراك — كل هذا
     * داخل معاملة واحدة (DB::transaction) مع lockForUpdate على الاشتراك:
     * لو فشلت أي خطوة (العداد الهدف لم يعد فعّالًا، أو تعارض عقد على نفس
     * المولد/الفترة)، تتراجع المعاملة بالكامل ويبقى الطلب "pending" —
     * لا يمكن أبدًا أن يصير الطلب "approved" دون أن يُطبَّق النقل فعليًا.
     */
    public function execute(SubscriptionMeterTransferRequest $transferRequest, User $reviewer): SubscriptionMeterTransferRequest
    {
        return DB::transaction(function () use ($transferRequest, $reviewer) {
            $transferRequest = SubscriptionMeterTransferRequest::lockForUpdate()->findOrFail($transferRequest->id);

            if (! $transferRequest->status->isReviewable()) {
                throw ValidationException::withMessages([
                    'status' => ['هذا الطلب ليس بانتظار المراجعة.'],
                ]);
            }

            $subscription = Subscription::lockForUpdate()->findOrFail($transferRequest->subscription_id);

            $toMeter = SubscriberMeter::where('id', $transferRequest->to_subscriber_meter_id)
                ->where('status', SubscriberMeterStatus::Active)
                ->first();

            if (! $toMeter) {
                throw ValidationException::withMessages([
                    'to_subscriber_meter_id' => ['العداد الهدف لم يعد فعّالًا — تعذّر إتمام النقل.'],
                ]);
            }

            $generator = $subscription->generator()->lockForUpdate()->firstOrFail();

            // إعادة التحقق: ما في عقد آخر فعّال/معلّق يستخدم نفس العداد الجديد
            // مع نفس المولد ونفس نوع الفترة (نفس فحص الازدواجية المستخدم عند
            // إنشاء اشتراك جديد) — يمنع تعارض العداد الجديد بعد النقل.
            $this->capacityService->assertNoDuplicateContract(
                $generator,
                $toMeter,
                $subscription->schedule,
                $subscription->service_start_time,
                $subscription->service_end_time,
                excludeSubscriptionId: $subscription->id
            );

            $subscription->forceFill(['subscriber_meter_id' => $toMeter->id])->save();

            $transferRequest->forceFill([
                'status' => SubscriptionMeterTransferStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();

            return $transferRequest->fresh(['subscription.generator', 'fromMeter', 'toMeter', 'requestedBy', 'reviewedBy']);
        });
    }
}
