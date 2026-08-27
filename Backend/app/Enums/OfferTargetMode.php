<?php

namespace App\Enums;

enum OfferTargetMode: string
{
    case All = 'all';
    case Beneficiary = 'beneficiary';
    case Selected = 'selected';

    public function label(): string
    {
        return match ($this) {
            self::All => 'جميع المشتركين',
            self::Beneficiary => 'حسب فئة المستفيدين',
            self::Selected => 'مشتركون محدَّدون يدويًا',
        };
    }
}
