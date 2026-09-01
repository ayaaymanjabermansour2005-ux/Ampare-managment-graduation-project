<?php

namespace App\Listeners;

use App\Enums\Role;
use App\Events\ComplaintSubmitted;
use App\Models\User;
use App\Notifications\NewComplaintNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendNewComplaintNotification implements ShouldQueue
{
    public function handle(ComplaintSubmitted $event): void
    {
        $admins = User::role(Role::ADMIN->value)->get();

        Notification::send($admins, new NewComplaintNotification($event->complaint));
    }
}
