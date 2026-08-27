<?php

namespace App\Enums;

enum TechnicianTaskStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case OnTheWay = 'on_the_way';
    case InProgress = 'in_progress';
    case WaitingParts = 'waiting_parts';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار التعيين',
            self::Assigned => 'بانتظار التحرك',
            self::OnTheWay => 'الفني بالطريق',
            self::InProgress => 'قيد التنفيذ',
            self::WaitingParts => 'بانتظار قطع غيار',
            self::Submitted => 'بانتظار المراجعة',
            self::Approved => 'معتمدة',
            self::Rejected => 'مرفوضة',
            self::Cancelled => 'ملغاة',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Assigned,
            self::OnTheWay,
            self::InProgress,
            self::WaitingParts,
        ], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Cancelled], true);
    }
}
