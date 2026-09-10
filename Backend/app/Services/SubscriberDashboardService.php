<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OperatingSchedule;
use App\Enums\SubscriptionStatus;
use App\Models\Complaint;
use App\Models\Generator;
use App\Models\GeneratorSchedule;
use App\Models\Invoice;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SubscriberDashboardService
{
    public function stats(User $user): array
    {
        $subscriber = $user->subscriber;

        if (! $subscriber) {
            return [
                'active_subscriptions_count' => 0,
                'pending_invoices_count' => 0,
                'overdue_invoices_count' => 0,
                'total_paid_ils' => 0.0,
                'open_complaints_count' => 0,
                'unread_notifications_count' => $user->unreadNotifications()->count(),
                'generator_status' => null,
                'upcoming_schedule' => null,
            ];
        }

        $meterIds = SubscriberMeter::where('subscriber_id', $subscriber->id)->pluck('id');

        $activeSubscription = Subscription::whereIn('subscriber_meter_id', $meterIds)
            ->where('status', SubscriptionStatus::Active)
            ->with('generator')
            ->latest('id')
            ->first();

        $generator = $activeSubscription?->generator;

        return [
            'active_subscriptions_count' => Subscription::whereIn('subscriber_meter_id', $meterIds)
                ->where('status', SubscriptionStatus::Active)->count(),

            'pending_invoices_count' => Invoice::whereHas(
                'subscription',
                fn ($q) => $q->whereIn('subscriber_meter_id', $meterIds)
            )->where('status', InvoiceStatus::Pending)->count(),

            'overdue_invoices_count' => Invoice::whereHas(
                'subscription',
                fn ($q) => $q->whereIn('subscriber_meter_id', $meterIds)
            )->where('status', InvoiceStatus::Overdue)->count(),

            'total_paid_ils' => (float) Invoice::whereHas(
                'subscription',
                fn ($q) => $q->whereIn('subscriber_meter_id', $meterIds)
            )->where('status', InvoiceStatus::Paid)->sum('final_amount_ils'),

            'open_complaints_count' => Complaint::where('submitted_by', $user->id)
                ->whereIn('status', [
                    ComplaintStatus::Pending->value,
                    ComplaintStatus::InProgress->value,
                    ComplaintStatus::WaitingSubscriber->value,
                ])
                ->count(),

            'unread_notifications_count' => $user->unreadNotifications()->count(),

            // generator_id مُضاف هنا (مو جوا generatorStatus()) عشان الواجهة
            // تقدر تربط "عرض جدول المولد" بصفحة تفاصيل المولد الصحيحة —
            // كان غير موجود بالرد أصلاً فالرابط كان مكسور دايمًا.
            'generator_status' => $generator ? ['generator_id' => $generator->id, ...$this->generatorStatus($generator)] : null,

            'upcoming_schedule' => $generator ? $this->upcomingSchedule($generator) : null,
        ];
    }

    /**
     * يحسب حالة تشغيل المولد الآن + موعد أقرب تغيير قادم (تشغيل/إيقاف)،
     * وفق أولوية واضحة:
     * 1) جدول تشغيل صريح (GeneratorSchedule) يغطي "الآن".
     * 2) أقرب جدول تشغيل صريح مستقبلي.
     * 3) النمط الثابت المعلن على المولد نفسه (operating_schedule + start/end time).
     */
    private function generatorStatus(Generator $generator): array
    {
        $now = now();

        $activeSchedule = $generator->schedules()
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->orderBy('starts_at')
            ->first();

        if ($activeSchedule) {
            return [
                'is_running' => true,
                'next_change_at' => $activeSchedule->ends_at->toIso8601String(),
            ];
        }

        $futureSchedule = $generator->schedules()
            ->where('starts_at', '>', $now)
            ->orderBy('starts_at')
            ->first();

        if ($futureSchedule) {
            return [
                'is_running' => false,
                'next_change_at' => $futureSchedule->starts_at->toIso8601String(),
            ];
        }

        return $this->statusFromStaticPattern($generator, $now);
    }

    /**
     * يرجع أقرب جدول تشغيل صريح قادم (GeneratorSchedule) للمولد، إن وُجد.
     */
    private function upcomingSchedule(Generator $generator): ?array
    {
        $schedule = $generator->schedules()
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->first();

        if (! $schedule) {
            return null;
        }

        return [
            'starts_at' => $schedule->starts_at->toIso8601String(),
            'ends_at' => $schedule->ends_at->toIso8601String(),
            'note' => $schedule->note,
        ];
    }

    /**
     * يحسب حالة التشغيل من النمط الثابت المعلن على المولد نفسه، لما ما في
     * أي جدول تشغيل صريح (حالي أو قادم) يغطي هذه الفترة.
     */
    private function statusFromStaticPattern(Generator $generator, CarbonInterface $now): array
    {
        if ($generator->operating_schedule === OperatingSchedule::TwentyFourHours) {
            return ['is_running' => true, 'next_change_at' => null];
        }

        $startTime = $generator->operating_start_time;
        $endTime = $generator->operating_end_time;

        if (! $startTime || ! $endTime) {
            // ما في وقت بداية/نهاية معلن — ما بنخمّن الحالة.
            return ['is_running' => null, 'next_change_at' => null];
        }

        $todayStart = Carbon::parse($now)->setTimeFromTimeString($startTime);
        $todayEnd = Carbon::parse($now)->setTimeFromTimeString($endTime);

        $isOvernight = $todayEnd->lessThanOrEqualTo($todayStart);

        if (! $isOvernight) {
            if ($now->betweenIncluded($todayStart, $todayEnd)) {
                return ['is_running' => true, 'next_change_at' => $todayEnd->toIso8601String()];
            }

            $nextStart = $now->lessThan($todayStart) ? $todayStart : $todayStart->copy()->addDay();

            return ['is_running' => false, 'next_change_at' => $nextStart->toIso8601String()];
        }

        // فترة تعبر منتصف الليل (مثلاً: 18:00 → 06:00) — فيه نافذتان محتملتان:
        // النافذة التي بدأت أمس وتنتهي اليوم، والنافذة التي تبدأ اليوم وتنتهي غدًا.
        $windowStartedYesterday = $todayStart->copy()->subDay();
        $windowEndsTomorrow = $todayEnd->copy()->addDay();

        if ($now->betweenIncluded($windowStartedYesterday, $todayEnd)) {
            return ['is_running' => true, 'next_change_at' => $todayEnd->toIso8601String()];
        }

        if ($now->betweenIncluded($todayStart, $windowEndsTomorrow)) {
            return ['is_running' => true, 'next_change_at' => $windowEndsTomorrow->toIso8601String()];
        }

        $nextStart = $now->lessThan($todayStart) ? $todayStart : $todayStart->copy()->addDay();

        return ['is_running' => false, 'next_change_at' => $nextStart->toIso8601String()];
    }
}
