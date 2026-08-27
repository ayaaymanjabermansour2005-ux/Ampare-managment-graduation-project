<?php

namespace App\Enums;

enum ServiceRequestEventType: string
{
    case Wedding = 'wedding';
    case Exam = 'exam';
    case ReligiousEvent = 'religious_event';
    case FamilyEvent = 'family_event';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Wedding => 'عرس',
            self::Exam => 'امتحان',
            self::ReligiousEvent => 'مناسبة دينية',
            self::FamilyEvent => 'مناسبة عائلية',
            self::Other => 'أخرى',
        };
    }
}
