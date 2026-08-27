<?php

namespace App\Actions\SubscriptionServiceRequest;

use App\DTOs\SubscriptionServiceRequest\CreateServiceRequestData;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionServiceRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class CreateServiceRequestAction
{
    public function execute(CreateServiceRequestData $data, User $user): SubscriptionServiceRequest
    {
        $subscription = Subscription::with('subscriberMeter.subscriber', 'generator')
            ->findOrFail($data->subscriptionId);

        $this->assertSubscriptionBelongsToUser($subscription, $user);
        $this->assertSubscriptionIsActive($subscription);

        $serviceRequest = SubscriptionServiceRequest::create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => $data->requestType,
            'event_type' => $data->eventType,
            'description' => $data->description,
            'extra_capacity_kw' => $data->extraCapacityKw,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
        ]);

        return $serviceRequest->fresh(['subscription.generator', 'requestedBy']);
    }

    private function assertSubscriptionBelongsToUser(Subscription $subscription, User $user): void
    {
        $ownerUserId = $subscription->subscriberMeter->subscriber->user_id;

        if ($ownerUserId !== $user->id) {
            throw ValidationException::withMessages([
                'subscription_id' => ['هذا الاشتراك لا يخصك.'],
            ]);
        }
    }

    private function assertSubscriptionIsActive(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            throw ValidationException::withMessages([
                'subscription_id' => ['لا يمكن تقديم طلب خدمة على اشتراك غير نشط.'],
            ]);
        }
    }
}
