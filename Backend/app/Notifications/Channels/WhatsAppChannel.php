<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $to = $this->resolveRecipient($notifiable, $notification);

        if (blank($to)) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        if (blank($message)) {
            return;
        }

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from');

        if (blank($sid) || blank($token) || blank($from)) {
            Log::warning('WhatsApp notification skipped: Twilio credentials not configured.');

            return;
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => "whatsapp:{$from}",
                'To' => "whatsapp:{$this->normalizePhone($to)}",
                'Body' => $message,
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp notification failed to send.', [
                'notifiable_id' => $notifiable->id ?? null,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function resolveRecipient(object $notifiable, Notification $notification): ?string
    {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $routed = $notifiable->routeNotificationFor('whatsapp', $notification);

            if (filled($routed)) {
                return $routed;
            }
        }

        return $notifiable->whatsapp ?? $notifiable->phone ?? null;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        return str_starts_with($phone, '+') ? $phone : '+' . $phone;
    }
}
