<?php

namespace App\Enums;

enum PaymentReviewStatus: string
{
    case Approved = 'approved';
    case NeedsCorrection = 'needs_correction';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'اعتماد',
            self::NeedsCorrection => 'طلب تعديل',
            self::Rejected => 'رفض',
        };
    }
}
