<?php

namespace App\Enums;

enum ServiceRequestType: string
{
    case Event = 'event';
    case ExtraCapacity = 'extra_capacity';
    case ExtraHours = 'extra_hours';
    case Maintenance = 'maintenance';

    case MedicalPriority = 'medical_priority';

    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'مناسبة',
            self::ExtraCapacity => 'زيادة سعة مؤقتة',
            self::ExtraHours => 'ساعات تشغيل إضافية',
            self::Maintenance => 'صيانة',
            self::MedicalPriority => 'حالة طبية (أولوية عالية)',
            self::Other => 'أخرى',
        };
    }

    public function isHighPriority(): bool
    {
        return $this === self::MedicalPriority;
    }
}
