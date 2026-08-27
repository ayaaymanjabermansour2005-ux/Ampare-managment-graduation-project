<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $expireMinutes = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور - Ampare')
            ->greeting('مرحبًا ' . $notifiable->name)
            ->line('وصلنا طلب لإعادة تعيين كلمة مرور حسابك. اضغط الزر أدناه لإنشاء كلمة مرور جديدة.')
            ->action('إعادة تعيين كلمة المرور', $resetUrl)
            ->line("هذا الرابط صالح لمدة {$expireMinutes} دقيقة فقط.")
            ->line('إذا لم تطلب إعادة تعيين كلمة المرور، تجاهل هذه الرسالة ولن يتغيّر شيء في حسابك.');
    }

    protected function resetUrl(object $notifiable): string
    {
        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');

        return sprintf(
            '%s/reset-password?token=%s&email=%s',
            $frontendUrl,
            $this->token,
            urlencode($notifiable->getEmailForPasswordReset())
        );
    }
}
