<?php

namespace App\Enums;

enum FaultStatus: string
{
    case PendingVerification = 'pending_verification';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case InRepair = 'in_repair';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::PendingVerification => 'بانتظار التحقق',
            self::Verified => 'تم التحقق منه',
            self::Rejected => 'بلاغ غير صحيح',
            self::InRepair => 'قيد الإصلاح',
            self::Resolved => 'تم الإصلاح',
            self::Closed => 'مغلق',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Rejected, self::Closed], true);
    }
}
