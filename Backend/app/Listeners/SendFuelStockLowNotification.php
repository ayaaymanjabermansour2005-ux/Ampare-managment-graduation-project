<?php

namespace App\Listeners;

use App\Events\FuelStockLow;
use App\Notifications\FuelStockLowNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class SendFuelStockLowNotification implements ShouldQueue
{
    public function handle(FuelStockLow $event): void
    {
        $key = "fuel-stock-low-alert-lock:{$event->generator->id}";

        if (Cache::has($key)) {
            return;
        }
        Cache::put($key, true, now()->addSeconds(10));

        $recipient = $event->generator->owner;

        if (NotificationPreferenceGate::allows($recipient, 'notify_fuel_stock_low')) {
            $recipient->notify(new FuelStockLowNotification($event->generator, $event->status));
        }
    }
}
