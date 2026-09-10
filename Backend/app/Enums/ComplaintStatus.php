<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case WaitingSubscriber = 'waiting_subscriber';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::InProgress => 'قيد المعالجة',
            self::WaitingSubscriber => 'بانتظار رد المشترك',
            self::Resolved => 'تم الحل',
        };
    }
}
