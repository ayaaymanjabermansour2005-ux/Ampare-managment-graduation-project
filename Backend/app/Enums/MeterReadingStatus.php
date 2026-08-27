<?php

namespace App\Enums;

enum MeterReadingStatus: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'بانتظار الاعتماد',
            self::Approved => 'معتمدة',
            self::Rejected => 'مرفوضة',
        };
    }
}
