<?php

namespace App\Listeners;

use App\Enums\SubscriptionStatus;
use App\Events\GeneratorScheduleAnnounced;
use App\Models\User;
use App\Notifications\GeneratorScheduleAnnouncedNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class SendGeneratorScheduleAnnouncedNotification implements ShouldQueue
{
    public function handle(GeneratorScheduleAnnounced $event): void
    {
        $key = "schedule-announced-lock:{$event->schedule->id}";

        if (Cache::has($key)) {
            return;
        }
        Cache::put($key, true, now()->addSeconds(10));

        $schedule = $event->schedule;

        $subscriberUsers = User::whereHas('subscriber.subscriptions', function ($q) use ($schedule) {
            $q->where('subscriptions.generator_id', $schedule->generator_id)
                ->where('subscriptions.status', SubscriptionStatus::Active->value);
        })->get()->filter(
            fn(User $u) => NotificationPreferenceGate::allows($u, 'notify_generator_schedule_announced')
        );

        if ($subscriberUsers->isNotEmpty()) {
            Notification::send($subscriberUsers, new GeneratorScheduleAnnouncedNotification($schedule));
        }
    }
}
