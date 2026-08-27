<?php

namespace App\Enums;

enum OperatingSchedule: string
{
    case Day = 'day';
    case Night = 'night';
    case TwentyFourHours = '24h';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'فترة نهارية',
            self::Night => 'فترة ليلية',
            self::TwentyFourHours => '24 ساعة',
            self::Custom => 'فترة مخصصة',
        };
    }
}
