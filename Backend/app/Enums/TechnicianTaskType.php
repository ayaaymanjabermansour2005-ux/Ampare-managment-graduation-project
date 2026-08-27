<?php

namespace App\Enums;

enum TechnicianTaskType: string
{
    case NewSubscriptionInstallation = 'new_subscription_installation';
    case MeterReading = 'meter_reading';
    case WiringMaintenance = 'wiring_maintenance';
    case FaultRepair = 'fault_repair';
    case GeneralMaintenance = 'general_maintenance';

    public function label(): string
    {
        return match ($this) {
            self::NewSubscriptionInstallation => 'تركيب اشتراك جديد',
            self::MeterReading => 'قراءة عداد',
            self::WiringMaintenance => 'صيانة تمديدات',
            self::FaultRepair => 'إصلاح عطل',
            self::GeneralMaintenance => 'صيانة عامة',
        };
    }
}
