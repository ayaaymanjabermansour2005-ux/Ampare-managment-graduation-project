<?php

namespace App\Services;

use App\Models\SubscriptionServiceRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubscriptionServiceRequestService
{
    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = SubscriptionServiceRequest::query()
            ->with(['subscription.generator', 'requestedBy', 'reviewedBy', 'override', 'invoice']);

        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->whereHas('subscription.generator', fn ($q) => $q->where('owner_id', $user->id));
        } elseif ($user->isSubscriber()) {
            $query->where('requested_by', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->latest()->paginate($perPage);
    }
}
