<?php

namespace App\Enums;

enum FaultPredictionStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Confirmed => 'مؤكَّد',
            self::Dismissed => 'مرفوض',
        };
    }
}
