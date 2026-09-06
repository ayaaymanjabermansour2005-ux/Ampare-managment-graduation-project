<?php

namespace App\Enums;

enum ComplaintPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'عادية',
            self::Medium => 'متوسطة',
            self::High => 'عالية',
            self::Urgent => 'عاجلة',
        };
    }
}
