<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verifyUrl = $this->verificationUrl($notifiable);

        $expireMinutes = config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('تفعيل البريد الإلكتروني - Ampare')
            ->greeting('مرحبًا '.$notifiable->name)
            ->line('يرجى الضغط على الزر أدناه لتفعيل بريدك الإلكتروني وإتمام تسجيل حسابك.')
            ->action('تفعيل البريد الإلكتروني', $verifyUrl)
            ->line("هذا الرابط صالح لمدة {$expireMinutes} دقيقة فقط.")
            ->line('إذا لم تقم بإنشاء هذا الحساب، تجاهل هذه الرسالة.');
    }

    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
