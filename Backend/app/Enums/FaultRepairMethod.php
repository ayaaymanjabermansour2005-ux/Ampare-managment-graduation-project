<?php

namespace App\Enums;

enum FaultRepairMethod: string
{
    case OwnerFixed = 'owner_fixed';
    case InternalTechnician = 'internal_technician';

    public function label(): string
    {
        return match ($this) {
            self::OwnerFixed => 'أصلحه المالك مباشرة',
            self::InternalTechnician => 'فني خاص',
        };
    }

    public function requiresTechnicianTask(): bool
    {
        return $this !== self::OwnerFixed;
    }
}
