<?php

namespace App\Notifications\Concerns;

trait NotifiesViaWhatsApp
{
    protected function whatsAppChannelIfAvailable(object $notifiable): array
    {
        $number = $notifiable->whatsapp ?? $notifiable->phone ?? null;

        return filled($number) ? ['whatsapp'] : [];
    }
}
