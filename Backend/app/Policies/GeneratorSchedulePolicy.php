<?php

namespace App\Policies;

use App\Models\GeneratorSchedule;
use App\Models\User;

class GeneratorSchedulePolicy
{
    public function view(User $user, GeneratorSchedule $schedule): bool
    {
        if (! $user->can('generator-schedules.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $schedule->generator->owner_id === $user->id;
        }

        if ($user->isSubscriber()) {
            return $schedule->generator->subscriptions()
                ->whereHas('subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $user->id))
                ->exists();
        }

        return false;
    }

    public function update(User $user, GeneratorSchedule $schedule): bool
    {
        if (! $user->can('generator-schedules.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOwner() && $schedule->generator->owner_id === $user->id;
    }

    public function delete(User $user, GeneratorSchedule $schedule): bool
    {
        return $this->update($user, $schedule);
    }
}
