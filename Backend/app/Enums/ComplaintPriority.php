<?php

namespace App\Enums;

enum ComplaintPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'عادية',
            self::Medium => 'متوسطة',
            self::High => 'عالية',
            self::Urgent => 'عاجلة',
        };
    }

    /**
     * سياسة SLA: عدد الساعات المسموح بها للرد/الحل حسب الأولوية، تُستخدم
     * لحساب complaints.sla_due_at لحظة تقديم الشكوى.
     */
    public function slaHours(): int
    {
        return match ($this) {
            self::Urgent => 4,
            self::High => 24,
            self::Medium => 72,
            self::Low => 168,
        };
    }
}
