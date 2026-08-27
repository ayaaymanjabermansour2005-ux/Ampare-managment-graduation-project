<?php

namespace App\Enums;

enum BeneficiaryType: string
{
    case Normal = 'normal';
    case Special = 'special';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'عادي',
            self::Special => 'مستفيد من تبرّع',
        };
    }
}
