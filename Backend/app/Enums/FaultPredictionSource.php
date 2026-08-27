<?php

namespace App\Enums;

enum FaultPredictionSource: string
{
    case ExternalMl = 'external_ml';
    case Chat = 'chat';
    case SensorAnalysis = 'sensor_analysis';

    public function label(): string
    {
        return match ($this) {
            self::ExternalMl => 'نظام تحليل خارجي',
            self::Chat => 'محادثة المساعد الذكي',
            self::SensorAnalysis => 'تحليل قراءات المحرك',
        };
    }
}
