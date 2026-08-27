<?php

namespace App\Listeners;

use App\Events\GeneratorHealthReportGenerated;
use App\Notifications\GeneratorHealthReportNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class SendGeneratorHealthReportNotification implements ShouldQueue
{
    public function handle(GeneratorHealthReportGenerated $event): void
    {
        $key = "health-report-lock:{$event->report->id}";
        if (Cache::has($key)) {
            return;
        }
        Cache::put($key, true, now()->addSeconds(10));

        $recipient = $event->report->generator->owner;

        if (NotificationPreferenceGate::allows($recipient, 'notify_generator_health_report')) {
            $recipient->notify(new GeneratorHealthReportNotification($event->report));
        }
    }
}
