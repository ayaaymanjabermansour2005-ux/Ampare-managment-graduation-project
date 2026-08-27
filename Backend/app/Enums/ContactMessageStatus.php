<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديدة',
            self::InProgress => 'قيد المعالجة',
            self::Resolved => 'تم الحل',
        };
    }
}
