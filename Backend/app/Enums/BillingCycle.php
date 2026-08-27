<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function labelAr(): string
    {
        return match ($this) {
            self::Daily => 'يومي',
            self::Weekly => 'أسبوعي',
            self::Monthly => 'شهري',
        };
    }

    public function intervalDays(): int
    {
        return match ($this) {
            self::Daily => 1,
            self::Weekly => 7,
            self::Monthly => 30,
        };
    }
}
