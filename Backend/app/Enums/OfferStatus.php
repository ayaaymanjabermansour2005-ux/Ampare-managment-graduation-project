<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'فعّال',
            self::Cancelled => 'ملغى',
        };
    }
}
