<?php

namespace App\Enums;

enum ContactMessageSubject: string
{
    case General = 'general';
    case Owner = 'owner';
    case Technical = 'technical';
    case Partnership = 'partnership';

    public function label(): string
    {
        return match ($this) {
            self::General => 'استفسار عام',
            self::Owner => 'أريد الانضمام كصاحب مولد',
            self::Technical => 'مشكلة تقنية',
            self::Partnership => 'شراكة',
        };
    }
}
