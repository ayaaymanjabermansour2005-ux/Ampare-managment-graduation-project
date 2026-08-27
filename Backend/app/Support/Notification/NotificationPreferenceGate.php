<?php

namespace App\Support\Notification;

use App\Models\User;
use App\Models\UserPreference;
use App\Services\UserPreferenceService;

class NotificationPreferenceGate
{
    private const CRITICAL_KEYS = [
        'notify_fault_reported',
    ];

    public static function allows(?User $user, string $preferenceKey): bool
    {
        if (! $user) {
            return false;
        }

        if (! self::typeEnabled($user, $preferenceKey)) {
            return false;
        }

        if (in_array($preferenceKey, self::CRITICAL_KEYS, true)) {
            return true;
        }

        if (app(UserPreferenceService::class)->isWithinQuietHours($user)) {
            return false;
        }

        return true;
    }

    private static function typeEnabled(User $user, string $preferenceKey): bool
    {
        $stored = UserPreference::where('user_id', $user->id)
            ->where('key', $preferenceKey)
            ->value('value');

        if ($stored === null) {
            return true;
        }

        return in_array($stored, ['1', 1, true, 'true'], true);
    }
}
