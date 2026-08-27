<?php

namespace App\Enums;

enum GeneratorStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';
    case PendingVerification = 'pending_verification';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Inactive => 'غير نشط',
            self::Maintenance => 'تحت الصيانة',
            self::PendingVerification => 'بانتظار اعتماد الأدمن',
            self::Rejected => 'مرفوض',
        };
    }
}
