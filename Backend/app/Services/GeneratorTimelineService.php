<?php

namespace App\Services;

use App\Models\Generator;
use Illuminate\Support\Collection;

class GeneratorTimelineService
{
    public function build(Generator $generator, int $days = 30): Collection
    {
        $since = now()->subDays($days);
        $events = collect();

        $generator->faults()->where('created_at', '>=', $since)->get()->each(function ($fault) use ($events) {
            $events->push([
                'type' => 'fault',
                'title' => 'بلاغ عطل',
                'description' => $fault->description,
                'date' => $fault->created_at,
            ]);
        });

        $generator->diagnosticReadings()->where('reading_date', '>=', $since)->get()->each(function ($reading) use ($events) {
            $events->push([
                'type' => 'diagnostic',
                'title' => 'قراءة تشخيصية',
                'description' => "ساعات تشغيل: {$reading->operating_hours}",
                'date' => $reading->reading_date,
            ]);
        });

        $generator->healthReports()->where('created_at', '>=', $since)->get()->each(function ($report) use ($events) {
            $events->push([
                'type' => 'health_report',
                'title' => 'تقرير صحة',
                'description' => $report->summary,
                'date' => $report->created_at,
            ]);
        });

        $generator->schedules()->where('created_at', '>=', $since)->get()->each(function ($schedule) use ($events) {
            $events->push([
                'type' => 'schedule',
                'title' => 'جدول تشغيل معلَن',
                'description' => "{$schedule->starts_at->format('H:i')} — {$schedule->ends_at->format('H:i')}",
                'date' => $schedule->created_at,
            ]);
        });

        return $events->sortByDesc('date')->values();
    }
}
