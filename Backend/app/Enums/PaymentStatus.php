<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case NeedsCorrection = 'needs_correction';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار المراجعة',
            self::NeedsCorrection => 'يحتاج تعديل',
            self::Paid => 'تم الدفع',
            self::Rejected => 'مرفوض',
            self::Cancelled => 'ملغي',
        };
    }

    public function canBeEdited(): bool
    {
        return match ($this) {
            self::Pending,
            self::NeedsCorrection => true,

            default => false,
        };
    }

    public function canBeDeleted(): bool
    {
        return match ($this) {
            self::Pending,
            self::NeedsCorrection => true,

            default => false,
        };
    }

    public function isReviewable(): bool
    {
        return match ($this) {
            self::Pending,
            self::NeedsCorrection => true,

            default => false,
        };
    }
}
