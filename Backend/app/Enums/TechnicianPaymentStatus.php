<?php

namespace App\Enums;

enum TechnicianPaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار مراجعة الفني',
            self::Approved => 'معتمدة',
            self::Rejected => 'مرفوضة',
        };
    }

    public function isReviewable(): bool
    {
        return $this === self::Pending;
    }
}
