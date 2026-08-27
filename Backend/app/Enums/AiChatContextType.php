<?php

namespace App\Enums;

enum AiChatContextType: string
{
    case OwnerDiagnostic = 'owner_diagnostic';
    case SubscriberSupport = 'subscriber_support';

    public function label(): string
    {
        return match ($this) {
            self::OwnerDiagnostic => 'تشخيص فني (مالك المولد)',
            self::SubscriberSupport => 'دعم مشترك',
        };
    }
}
