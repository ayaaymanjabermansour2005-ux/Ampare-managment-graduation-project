<?php

namespace App\Policies;

use App\Models\Generator;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('subscriptions.view')
            && ($user->isAdmin() || $user->isOwner() || $user->isSubscriber() || $user->isTechnician());
    }

    public function view(User $user, Subscription $subscription): bool
    {
        if (! $user->can('subscriptions.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $user->id === $subscription->generator?->owner_id;
        }

        if ($user->isSubscriber()) {
            return $user->id === $subscription->subscriberMeter?->subscriber?->user_id;
        }

        if ($user->isTechnician()) {
            return $subscription->generator
                ?->technicians()
                ->where('technicians.user_id', $user->id)
                ->exists() ?? false;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('subscriptions.create') && $user->isSubscriber();
    }

    /**
     * إنشاء اشتراك من قِبَل مالك المولد نيابةً عن مشترك — قدرة إضافية منفصلة
     * عن create() (الاشتراك الذاتي للمشترك)، مربوطة بمولد محدَّد (instance-scoped)
     * حتى لا يقدر المالك يُنشئ اشتراكًا على مولد لا يملكه.
     */
    public function createByOwner(User $user, Generator $generator): bool
    {
        return $user->can('subscriptions.create') && $user->isOwner() && $generator->owner_id === $user->id;
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return false;
    }

    public function updateStatus(User $user, Subscription $subscription): bool
    {
        if (! $user->can('subscriptions.updateStatus')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $user->id === $subscription->generator?->owner_id;
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return false;
    }

    public function restore(User $user, Subscription $subscription): bool
    {
        return false;
    }

    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return false;
    }

    public function rateOwner(User $user, Subscription $subscription): bool
    {
        if (! $user->isSubscriber()) {
            return false;
        }

        return $user->id === $subscription->subscriberMeter?->subscriber?->user_id;
    }

    public function transfer(User $user, Subscription $subscription): bool
    {
        return $user->can('subscriptions.transfer') && $user->isAdmin();
    }

    /**
     * نقل اشتراك بين مولدات مملوكة لنفس المالك — قدرة منفصلة عن transfer()
     * (المخصَّصة للأدمن فقط دون قيود على المولد الهدف). هنا نتحقق فقط من
     * أن المولد "المصدر" الحالي للاشتراك مملوك للمالك؛ التحقق من أن المولد
     * "الهدف" أيضًا مملوك لنفس المالك يتم داخل TransferSubscriptionAction
     * ضمن نفس الـ transaction (بعد lockForUpdate) لتفادي أي تلاعب زمني (TOCTOU).
     */
    public function transferByOwn(User $user, Subscription $subscription): bool
    {
        return $user->can('subscriptions.transfer') && $user->isOwner() && $subscription->generator?->owner_id === $user->id;
    }
}
