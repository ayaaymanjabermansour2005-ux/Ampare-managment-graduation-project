<?php

namespace App\Services;

use App\Models\SubscriptionMeterTransferRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubscriptionMeterTransferService
{
    public function list(User $user, int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        $query = SubscriptionMeterTransferRequest::query()->with([
            'subscription.generator',
            'subscription.subscriberMeter.subscriber.user',
            'fromMeter',
            'toMeter',
            'requestedBy',
            'reviewedBy',
        ]);

        if ($user->isAdmin()) {
            // بلا نطاق إضافي — عرض/تدقيق كامل.
        } elseif ($user->isOwner()) {
            $query->whereHas('subscription.generator', fn ($q) => $q->where('owner_id', $user->id));
        } elseif ($user->isSubscriber()) {
            $query->where('requested_by', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }
}
