<?php

namespace App\Enums;

enum HealthRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'مستقرة',
            self::Medium => 'تحتاج مراقبة',
            self::High => 'تحتاج تدخّل عاجل',
        };
    }
}
