<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentService
{
    public function list(User $user, int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        $query = Payment::query()
            ->with([
                'invoice.subscription.generator',
                'invoice.subscription.subscriberMeter.subscriber.user',
                'paymentMethod',
                'processedBy',
            ])
            ->withCount('attachments');

        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->whereHas(
                'invoice.subscription.generator',
                fn ($q) => $q->where('owner_id', $user->id)
            );
        } elseif ($user->isSubscriber()) {
            $query->whereHas(
                'invoice.subscription.subscriberMeter.subscriber',
                fn ($q) => $q->where('user_id', $user->id)
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }
}
