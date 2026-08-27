<?php

namespace App\Notifications;

use App\Enums\HealthRiskLevel;
use App\Models\GeneratorHealthReport;
use App\Notifications\Concerns\NotifiesViaWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GeneratorHealthReportNotification extends Notification
{
    use NotifiesViaWhatsApp, Queueable;

    public function __construct(public GeneratorHealthReport $report) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($this->report->risk_level === HealthRiskLevel::High) {
            $channels = [...$channels, ...$this->whatsAppChannelIfAvailable($notifiable)];
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تقرير صحة المولد الدوري',
            'message' => "حالة مولد \"{$this->report->generator->name}\": {$this->report->risk_level->label()}.",
            'generator_id' => $this->report->generator_id,
            'report_id' => $this->report->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "🚨 إنذار: مولدك يحتاج تدخّل عاجل\nالمولد: %s\nالحالة: %s\nيرجى مراجعة تقرير الصحة بلوحة التحكم فورًا.",
            $this->report->generator->name,
            $this->report->risk_level->label()
        );
    }
}
