<?php

namespace App\Enums;

enum PlatformCommissionStatus: string
{
    case Pending = 'pending';
    case Earned = 'earned';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::Earned => 'مستحقة',
            self::Paid => 'محوّلة',
        };
    }
}
