<?php

namespace App\Enums;

enum CommissionMode: string
{
    case Fixed = 'fixed';
    case Tiered = 'tiered';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'نسبة ثابتة',
            self::Tiered => 'شرائح تلقائية حسب عدد المولدات',
        };
    }
}
