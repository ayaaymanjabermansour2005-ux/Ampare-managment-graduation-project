<?php

namespace App\Enums;

enum PaymentSource: string
{
    case Subscriber = 'subscriber';
    case Adjustment = 'adjustment';
    case Gateway = 'gateway';

    public function label(): string
    {
        return match ($this) {
            self::Subscriber => 'دفعة من المشترك',
            self::Adjustment => 'تسوية إدارية',
            self::Gateway => 'بوابة دفع إلكتروني',
        };
    }
}
