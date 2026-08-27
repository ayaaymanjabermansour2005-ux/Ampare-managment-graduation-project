<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case Bank = 'bank';
    case Wallet = 'wallet';
    case Cash = 'cash';

    public function requiresAccountDetails(): bool
    {
        return $this !== self::Cash;
    }

    public function requiresCurrency(): bool
    {
        return $this !== self::Cash;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
