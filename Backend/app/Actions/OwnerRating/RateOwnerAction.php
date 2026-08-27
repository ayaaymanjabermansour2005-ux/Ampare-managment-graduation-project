<?php

namespace App\Actions\OwnerRating;

use App\Enums\SubscriptionStatus;
use App\Models\OwnerRating;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RateOwnerAction
{
    private const ELIGIBLE_STATUSES = [
        SubscriptionStatus::Active->value,
        SubscriptionStatus::Suspended->value,
        SubscriptionStatus::Cancelled->value,
    ];

    public function execute(Subscription $subscription, int $rating, ?string $comment, User $user): OwnerRating
    {
        return DB::transaction(function () use ($subscription, $rating, $comment, $user) {
            $subscription = Subscription::lockForUpdate()->with('generator')->findOrFail($subscription->id);

            if (! in_array($subscription->status->value, self::ELIGIBLE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'subscription' => ['لا يمكن تقييم صاحب المولد قبل تفعيل الاشتراك.'],
                ]);
            }

            if (OwnerRating::where('subscription_id', $subscription->id)->exists()) {
                throw ValidationException::withMessages([
                    'subscription' => ['تم تقييم صاحب المولد لهذا الاشتراك مسبقًا.'],
                ]);
            }

            return OwnerRating::create([
                'subscription_id' => $subscription->id,
                'owner_id' => $subscription->generator->owner_id,
                'rated_by' => $user->id,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        });
    }
}
