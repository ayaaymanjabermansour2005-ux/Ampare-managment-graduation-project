<?php

namespace App\Enums;

enum FaultSource: string
{
    case SubscriberReport = 'subscriber_report';
    case AiPrediction = 'ai_prediction';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::SubscriberReport => 'بلاغ من مشترك',
            self::AiPrediction => 'توقع ذكاء اصطناعي',
            self::Manual => 'إدخال يدوي',
        };
    }
}
