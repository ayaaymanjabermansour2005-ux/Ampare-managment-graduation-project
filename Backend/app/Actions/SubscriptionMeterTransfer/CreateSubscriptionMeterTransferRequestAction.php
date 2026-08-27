<?php

namespace App\Actions\SubscriptionMeterTransfer;

use App\DTOs\SubscriptionMeterTransfer\CreateSubscriptionMeterTransferRequestData;
use App\Enums\SubscriberMeterStatus;
use App\Enums\SubscriptionMeterTransferStatus;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\SubscriptionMeterTransferRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class CreateSubscriptionMeterTransferRequestAction
{
    public function execute(CreateSubscriptionMeterTransferRequestData $data, User $user): SubscriptionMeterTransferRequest
    {
        $subscriber = $user->subscriber;

        if (! $subscriber) {
            throw ValidationException::withMessages([
                'subscriber' => ['حساب المشترك غير مكتمل، يرجى إكمال الملف الشخصي أولاً.'],
            ]);
        }

        $subscription = Subscription::with('subscriberMeter.subscriber')->findOrFail($data->subscriptionId);

        if ($subscription->subscriberMeter?->subscriber?->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'subscription_id' => ['هذا الاشتراك لا يخصك.'],
            ]);
        }

        $toMeter = SubscriberMeter::where('id', $data->toSubscriberMeterId)
            ->where('subscriber_id', $subscriber->id)
            ->where('status', SubscriberMeterStatus::Active)
            ->first();

        if (! $toMeter) {
            throw ValidationException::withMessages([
                'to_subscriber_meter_id' => ['العداد المحدَّد غير موجود، غير فعّال، أو لا يخصك.'],
            ]);
        }

        if ($toMeter->id === $subscription->subscriber_meter_id) {
            throw ValidationException::withMessages([
                'to_subscriber_meter_id' => ['هذا هو العداد الحالي للاشتراك بالفعل.'],
            ]);
        }

        $hasOpenRequest = SubscriptionMeterTransferRequest::where('subscription_id', $subscription->id)
            ->where('status', SubscriptionMeterTransferStatus::Pending)
            ->exists();

        if ($hasOpenRequest) {
            throw ValidationException::withMessages([
                'subscription_id' => ['يوجد طلب نقل عداد قيد المراجعة بالفعل لهذا الاشتراك.'],
            ]);
        }

        $transferRequest = SubscriptionMeterTransferRequest::create([
            'subscription_id' => $subscription->id,
            'from_subscriber_meter_id' => $subscription->subscriber_meter_id,
            'to_subscriber_meter_id' => $toMeter->id,
            'requested_by' => $user->id,
            'status' => SubscriptionMeterTransferStatus::Pending,
            'reason' => $data->reason,
        ]);

        return $transferRequest->fresh(['subscription.generator', 'fromMeter', 'toMeter', 'requestedBy']);
    }
}
