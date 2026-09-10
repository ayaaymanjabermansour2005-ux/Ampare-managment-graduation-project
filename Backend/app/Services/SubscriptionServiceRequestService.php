<?php

namespace App\Services;

use App\Models\SubscriptionServiceRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionServiceRequestService
{
    /**
     * FIX (تدقيق شامل — B7): لا فلترة حالة ولا بحث إطلاقًا، خلافًا لكل
     * الواجهات الشقيقة (المولدات/الأعطال/قراءات العدادات).
     */
    public function list(User $user, int $perPage = 15, ?string $status = null, ?string $search = null): LengthAwarePaginator
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

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas(
                    'requestedBy',
                    fn ($u) => $u->where('name', 'like', "%{$search}%")
                )->orWhereHas(
                    'subscription.generator',
                    fn ($g) => $g->where('name', 'like', "%{$search}%")
                );
            });
        }

        return $query->latest()->paginate($perPage);
    }
}
