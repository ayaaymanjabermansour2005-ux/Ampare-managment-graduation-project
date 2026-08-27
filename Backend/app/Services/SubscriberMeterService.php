<?php

namespace App\Services;

use App\Enums\SubscriberMeterStatus;
use App\Enums\SubscriptionStatus;
use App\Models\SubscriberMeter;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SubscriberMeterService
{
    public function list(User $user): Collection
    {
        if ($user->isAdmin()) {
            return SubscriberMeter::query()->latest()->get();
        }

        if ($user->isSubscriber() && $user->subscriber) {
            return $user->subscriber->meters()->latest()->get();
        }

        return SubscriberMeter::query()->whereRaw('1 = 0')->get();
    }

    public function create(array $data, User $user): SubscriberMeter
    {
        $subscriber = $user->subscriber;

        if (! $subscriber) {
            throw ValidationException::withMessages([
                'subscriber' => ['حساب المشترك غير مكتمل، يرجى إكمال الملف الشخصي أولًا.'],
            ]);
        }

        return SubscriberMeter::create([
            'subscriber_id' => $subscriber->id,
            'meter_number' => $data['meter_number'],
            'property_label' => $data['property_label'] ?? null,
            'status' => SubscriberMeterStatus::Active->value,
        ]);
    }

    public function update(SubscriberMeter $meter, array $data): SubscriberMeter
    {
        $isDeactivating = isset($data['status'])
            && SubscriberMeterStatus::from($data['status']) === SubscriberMeterStatus::Inactive
            && $meter->status !== SubscriberMeterStatus::Inactive;

        if ($isDeactivating) {
            $this->assertNoActiveOrPendingSubscriptions($meter, 'تعطيل');
        }

        $meter->update($data);

        return $meter->fresh();
    }

    public function delete(SubscriberMeter $meter): void
    {
        $this->assertNoActiveOrPendingSubscriptions($meter, 'حذف');

        $meter->delete();
    }

    private function assertNoActiveOrPendingSubscriptions(SubscriberMeter $meter, string $action): void
    {
        $activeCount = $meter->subscriptions()->whereIn('status', [
            SubscriptionStatus::Pending->value,
            SubscriptionStatus::Active->value,
        ])->count();

        if ($activeCount > 0) {
            throw ValidationException::withMessages([
                'meter' => ["لا يمكن {$action} هذا العداد لأنه مرتبط بـ {$activeCount} عقد فعّال أو معلّق. يجب إنهاء هذه العقود أولًا."],
            ]);
        }
    }
}
