<?php

namespace App\Enums;

enum ReviewerRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'مالك المولد',
            self::Admin => 'أدمن (تدخل استثنائي)',
        };
    }
}
