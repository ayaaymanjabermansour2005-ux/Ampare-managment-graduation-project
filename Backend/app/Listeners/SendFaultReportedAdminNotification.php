<?php

namespace App\Listeners;

use App\Enums\Role;
use App\Events\FaultReported;
use App\Notifications\FaultReportedAdminNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendFaultReportedAdminNotification implements ShouldQueue
{
    public function handle(FaultReported $event): void
    {
        $fault = $event->fault->loadMissing('generator.owner');
        $admins = User::role(Role::ADMIN->value)->get();

        Notification::send($admins, new FaultReportedAdminNotification($fault));
    }
}
