<?php

namespace App\Listeners;

use App\Enums\Role;
use App\Events\ContactMessageReceived;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendNewContactMessageNotification implements ShouldQueue
{
    public function handle(ContactMessageReceived $event): void
    {
        $admins = User::role(Role::ADMIN->value)->get();

        Notification::send($admins, new NewContactMessageNotification($event->contactMessage));
    }
}
