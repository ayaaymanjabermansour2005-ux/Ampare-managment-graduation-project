<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case PendingReview = 'pending_review';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Inactive => 'غير نشط',
            self::Suspended => 'موقوف',
            self::PendingReview => 'بانتظار مراجعة الإدارة',
        };
    }
}
