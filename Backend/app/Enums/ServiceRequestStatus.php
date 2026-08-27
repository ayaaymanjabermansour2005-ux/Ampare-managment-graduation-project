<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار المراجعة',
            self::Approved => 'موافق عليه',
            self::Rejected => 'مرفوض',
            self::Cancelled => 'ملغى',
        };
    }
}
