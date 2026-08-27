<?php

namespace App\Support\Scheduling;

use App\Enums\OperatingSchedule;

final class ScheduleWindow
{
    public const BOUNDS = [
        'day' => ['start' => '06:00', 'end' => '18:00'],
        'night' => ['start' => '18:00', 'end' => '06:00'],
    ];

    public static function segmentsFor(OperatingSchedule $schedule, ?string $customStart = null, ?string $customEnd = null): array
    {
        if ($schedule === OperatingSchedule::TwentyFourHours) {
            return [[0, 1440]];
        }

        if ($schedule === OperatingSchedule::Custom) {
            return self::toSegments($customStart, $customEnd);
        }

        $bounds = self::BOUNDS[$schedule->value] ?? null;

        if (! $bounds) {
            throw new \InvalidArgumentException("نوع فترة تشغيل غير معروف: {$schedule->value}");
        }

        return self::toSegments($bounds['start'], $bounds['end']);
    }

    public static function overlaps(array $segmentsA, array $segmentsB): bool
    {
        foreach ($segmentsA as [$aStart, $aEnd]) {
            foreach ($segmentsB as [$bStart, $bEnd]) {
                if ($aStart < $bEnd && $bStart < $aEnd) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isSameWindow(array $segmentsA, array $segmentsB): bool
    {
        sort($segmentsA);
        sort($segmentsB);

        return $segmentsA === $segmentsB;
    }

    private static function toMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $h * 60 + $m;
    }

    private static function toSegments(string $start, string $end): array
    {
        $s = self::toMinutes($start);
        $e = self::toMinutes($end);

        if ($e > $s) {
            return [[$s, $e]];
        }

        if ($e < $s) {
            return [[$s, 1440], [0, $e]];
        }

        return [[0, 1440]];
    }
}
