<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Notifications\NewMessageNotification;
use App\Support\Notification\NotificationPreferenceGate;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMessageNotification implements ShouldQueue
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        $recipient = $message->conversation->otherParticipant($message->sender);

        if (NotificationPreferenceGate::allows($recipient, 'notify_new_message')) {
            $recipient->notify(new NewMessageNotification($message));
        }
    }
}
