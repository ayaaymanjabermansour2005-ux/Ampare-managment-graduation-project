<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::PartiallyPaid => 'مسدّدة جزئيًا',
            self::Paid => 'مسدّدة بالكامل',
            self::Overdue => 'متأخرة',
            self::Cancelled => 'ملغاة',
        };
    }
}
