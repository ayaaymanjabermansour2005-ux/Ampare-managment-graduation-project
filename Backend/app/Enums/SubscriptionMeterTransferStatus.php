<?php

namespace App\Enums;

enum SubscriptionMeterTransferStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار مراجعة المالك',
            self::Approved => 'تمت الموافقة والتحويل',
            self::Rejected => 'مرفوض',
        };
    }

    public function isReviewable(): bool
    {
        return $this === self::Pending;
    }
}
