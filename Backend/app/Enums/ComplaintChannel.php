<?php

namespace App\Enums;

enum ComplaintChannel: string
{
    case App = 'app';
    case Phone = 'phone';
    case Whatsapp = 'whatsapp';
    case Web = 'web';

    public function label(): string
    {
        return match ($this) {
            self::App => 'تطبيق',
            self::Phone => 'هاتف',
            self::Whatsapp => 'واتساب',
            self::Web => 'الموقع',
        };
    }
}
