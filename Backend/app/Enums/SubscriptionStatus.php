<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Active => 'نشط',
            self::Suspended => 'موقوف',
            self::Cancelled => 'ملغى',
            self::Rejected => 'مرفوض',
        };
    }
}
