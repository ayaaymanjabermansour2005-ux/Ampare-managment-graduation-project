<?php

namespace App\Enums;

enum OwnerApplicationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار المراجعة',
            self::Approved => 'مقبول',
            self::Rejected => 'مرفوض',
        };
    }
}
