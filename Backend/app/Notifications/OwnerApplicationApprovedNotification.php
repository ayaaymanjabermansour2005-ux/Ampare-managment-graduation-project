<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OwnerApplicationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database', 'broadcast'];

        if (filled($notifiable->whatsapp ?? $notifiable->phone ?? null)) {
            $channels[] = 'whatsapp';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . '/login';

        return (new MailMessage)
            ->subject('تمت الموافقة على طلب انضمامك كصاحب مولد - أمبير')
            ->greeting('مرحبًا ' . $notifiable->name . ' 🎉')
            ->line('تمت مراجعة طلب انضمامك كصاحب مولد على منصة أمبير، وتمت الموافقة عليه.')
            ->line('يمكنك الآن تسجيل الدخول باستخدام بريدك الإلكتروني وكلمة السر يلي حددتها أثناء تقديم الطلب.')
            ->line('مولدك اللي قدّمت بياناته أثناء التسجيل تمت إضافته لحسابك، وهو الآن قيد المراجعة الفنية النهائية من فريقنا — بيوصلك إشعار منفصل فور تفعيله بالكامل.')
            ->action('تسجيل الدخول الآن', $loginUrl);
    }

    public function toWhatsApp(object $notifiable): string
    {
        return "مرحبًا {$notifiable->name} 🎉\nتمت الموافقة على طلب انضمامك كصاحب مولد بمنصة أمبير.\nمولدك اللي سجّلته صار مرتبط بحسابك وقيد المراجعة الفنية النهائية حاليًا.\nتقدر تسجّل دخولك الآن من نفس البريد وكلمة السر يلي حددتها.";
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تمت الموافقة على طلب انضمامك',
            'message' => 'تمت الموافقة على طلب انضمامك كصاحب مولد. مولدك قيد المراجعة الفنية الآن.',
            'link_type' => 'owner_onboarding',
            'link_id' => $notifiable->id,
        ];
    }

    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage($this->toArray($notifiable));
    }
}
