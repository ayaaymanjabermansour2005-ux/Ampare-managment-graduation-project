<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:mark-overdue')->daily();

Schedule::command('activitylog:clean')->daily();

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('02:00');

Schedule::command('attachments:prune')->daily();

Schedule::command('idempotency:prune')->daily();
Schedule::command('generators:health-report --period=monthly')->monthlyOn(1, '02:00');
Schedule::command('invoices:remind-due-soon')->dailyAt('09:00');
Schedule::command('reports:owner-monthly')->monthlyOn(1, '08:00');

Schedule::command('demo:reset')->dailyAt('04:00');
