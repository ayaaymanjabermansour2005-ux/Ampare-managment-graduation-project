<?php

namespace App\Services;

use App\Enums\OfferDiscountType;
use App\Enums\OfferStatus;
use App\Enums\OfferTargetMode;
use App\Models\Offer;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OfferService
{
    public function list(User $user, int $perPage = 15, bool $includeExpired = false, ?string $search = null): LengthAwarePaginator
    {
        $query = Offer::query()->with('owner');

        if ($user->isAdmin()) {
        } elseif ($user->isOwner()) {
            $query->where('owner_id', $user->id);
        } elseif ($user->isSubscriber()) {
            $subscriber = $user->subscriber;

            if (! $subscriber) {
                $query->whereRaw('1 = 0');
            } else {
                $this->scopeVisibleToSubscriber($query, $subscriber);
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        if (! $includeExpired) {
            $query->where('status', OfferStatus::Active->value)->where('end_date', '>=', now()->toDateString());
        }

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data, User $owner): Offer
    {
        return DB::transaction(function () use ($data, $owner) {
            $offer = Offer::create([
                'owner_id' => $owner->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'target_mode' => $data['target_mode'],
                'beneficiary_type' => $data['target_mode'] === OfferTargetMode::Beneficiary->value
                    ? $data['beneficiary_type']
                    : null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => OfferStatus::Active->value,
            ]);

            if ($data['target_mode'] === OfferTargetMode::Selected->value) {
                $offer->targetedSubscribers()->sync($data['subscriber_ids']);
            }

            return $offer->load(['owner', 'targetedSubscribers']);
        });
    }

    public function update(Offer $offer, array $data): Offer
    {
        $offer->update(array_filter($data, fn($key) => in_array($key, [
            'title',
            'description',
            'discount_value',
            'start_date',
            'end_date',
        ], true), ARRAY_FILTER_USE_KEY));

        return $offer->fresh(['owner', 'targetedSubscribers']);
    }

    public function cancel(Offer $offer): Offer
    {
        $offer->update(['status' => OfferStatus::Cancelled->value]);

        return $offer->fresh(['owner', 'targetedSubscribers']);
    }

    private function scopeVisibleToSubscriber($query, Subscriber $subscriber): void
    {
        $ownerIds = $subscriber->subscriptions()
            ->with('generator')
            ->get()
            ->pluck('generator.owner_id')
            ->filter()
            ->unique()
            ->values();

        $query->whereIn('owner_id', $ownerIds)
            ->where(fn($q) => $this->applyTargetVisibility($q, $subscriber));
    }

    private function applyTargetVisibility($query, Subscriber $subscriber)
    {
        return $query
            ->where('target_mode', OfferTargetMode::All->value)
            ->orWhere(function ($q) use ($subscriber) {
                $q->where('target_mode', OfferTargetMode::Beneficiary->value)
                    ->where('beneficiary_type', $subscriber->beneficiary_type->value);
            })
            ->orWhere(function ($q) use ($subscriber) {
                $q->where('target_mode', OfferTargetMode::Selected->value)
                    ->whereHas('targetedSubscribers', fn($q3) => $q3->where('subscribers.id', $subscriber->id));
            });
    }

    /**
     * @return array{offer: Offer, discount_amount: float}|null
     */
    public function resolveBestOffer(Subscription $subscription, float $baseAmount): ?array
    {
        $subscriber = $subscription->subscriberMeter?->subscriber;

        if (! $subscriber) {
            return null;
        }

        $ownerId = $subscription->generator->owner_id;
        $today = now()->toDateString();

        $activeOffers = Offer::query()
            ->where('owner_id', $ownerId)
            ->where('status', OfferStatus::Active->value)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where(fn($q) => $this->applyTargetVisibility($q, $subscriber))
            ->get();

        if ($activeOffers->isEmpty()) {
            return null;
        }

        $candidates = $activeOffers->map(fn(Offer $offer) => [
            'offer' => $offer,
            'discount_amount' => $this->calculateDiscountAmount($offer, $baseAmount),
        ]);

        $bySpecificity = $candidates->sortByDesc(function ($c) {
            return match ($c['offer']->target_mode) {
                OfferTargetMode::Selected => 3,
                OfferTargetMode::Beneficiary => 2,
                OfferTargetMode::All => 1,
            };
        });

        $topSpecificity = $bySpecificity->first()['offer']->target_mode;
        $pool = $candidates->filter(fn($c) => $c['offer']->target_mode === $topSpecificity);

        return $pool->sortByDesc('discount_amount')->first();
    }

    private function calculateDiscountAmount(Offer $offer, float $baseAmount): float
    {
        $raw = $offer->discount_type === OfferDiscountType::Percentage
            ? Money::percentage($baseAmount, (float) $offer->discount_value, 2)
            : (float) $offer->discount_value;

        return Money::min($raw, $baseAmount, 2);
    }
}
