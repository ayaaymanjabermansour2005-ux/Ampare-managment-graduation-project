<?php

namespace App\Enums;

enum FuelType: string
{
    case Diesel = 'diesel';
    case Gas = 'gas';
    case Petrol = 'petrol';
    case Dual = 'dual';

    public function label(): string
    {
        return match ($this) {
            self::Diesel => 'ديزل',
            self::Gas => 'غاز',
            self::Petrol => 'بنزين',
            self::Dual => 'مزدوج (ديزل/غاز)',
        };
    }
}
