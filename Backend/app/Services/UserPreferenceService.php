<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserPreferenceService
{
    private const DEFAULTS = [
        'shared' => [
            'language' => 'ar',
            'notify_new_message' => true,
            'dnd_enabled' => false,
            'dnd_days' => '0,1,2,3,4,5,6',
            'dnd_start_time' => '22:00',
            'dnd_end_time' => '07:00',
        ],
        'admin' => [
            'email_digest_enabled' => true,
        ],
        'generator_owner' => [
            'notify_fault_reported' => true,
            'notify_fuel_stock_low' => true,
            'notify_generator_health_report' => true,
            'notify_payment_submitted' => true,
            'notify_technician_task_submitted' => true,
            'notify_invoice_paid' => true,
            'notify_generator_verified' => true,
            'notify_generator_rejected' => true,
        ],
        'subscriber' => [
            'notify_complaint_resolved' => true,
            'notify_generator_schedule_announced' => true,
            'notify_subscription_approved' => true,
            'notify_invoice_paid' => true,
            'notify_invoice_due_soon' => true,
        ],
        'technician' => [
            'notify_technician_task_assigned' => true,
            'notify_technician_task_approved' => true,
            'notify_technician_task_rejected' => true,
        ],
    ];

    private const TIME_KEYS = ['dnd_start_time', 'dnd_end_time'];

    private const DAYS_KEY = 'dnd_days';

    private function allowedKeysFor(User $user): array
    {
        $roleKeys = match (true) {
            $user->isAdmin() => self::DEFAULTS['admin'],
            $user->isOwner() => self::DEFAULTS['generator_owner'],
            $user->isSubscriber() => self::DEFAULTS['subscriber'],
            $user->isTechnician() => self::DEFAULTS['technician'],
            default => [],
        };

        return array_merge(self::DEFAULTS['shared'], $roleKeys);
    }

    public function list(User $user): array
    {
        $defaults = $this->allowedKeysFor($user);

        $stored = $user->preferences()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key');

        return collect($defaults)
            ->map(fn ($default, $key) => $stored->has($key)
                ? $this->castLike($default, $stored[$key])
                : $default)
            ->all();
    }

    public function update(User $user, array $data): array
    {
        $allowedKeys = array_keys($this->allowedKeysFor($user));
        $toUpdate = array_intersect_key($data, array_flip($allowedKeys));

        if (empty($toUpdate)) {
            throw ValidationException::withMessages([
                'preferences' => ['لا يوجد أي تفضيل صالح بالبيانات المُرسَلة.'],
            ]);
        }

        $this->validateDndFields($toUpdate);

        DB::transaction(function () use ($user, $toUpdate) {
            foreach ($toUpdate as $key => $value) {
                UserPreference::updateOrCreate(
                    ['user_id' => $user->id, 'key' => $key],
                    ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
                );
            }
        });

        return $this->list($user->fresh());
    }

    private function validateDndFields(array $data): void
    {
        foreach (self::TIME_KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $data[$key])) {
                throw ValidationException::withMessages([
                    "preferences.{$key}" => ['صيغة الوقت يجب أن تكون HH:MM (مثال: 22:00).'],
                ]);
            }
        }

        if (array_key_exists(self::DAYS_KEY, $data)) {
            $days = array_filter(explode(',', (string) $data[self::DAYS_KEY]), fn ($d) => $d !== '');

            foreach ($days as $day) {
                if (! ctype_digit($day) || (int) $day < 0 || (int) $day > 6) {
                    throw ValidationException::withMessages([
                        'preferences.dnd_days' => ['أيام الأسبوع يجب أن تكون أرقامًا من 0 إلى 6 مفصولة بفواصل.'],
                    ]);
                }
            }
        }
    }

    public function isWithinQuietHours(User $user): bool
    {
        $prefs = $user->preferences()
            ->whereIn('key', ['dnd_enabled', 'dnd_days', 'dnd_start_time', 'dnd_end_time'])
            ->pluck('value', 'key');

        $enabled = $prefs->has('dnd_enabled')
            ? in_array($prefs['dnd_enabled'], ['1', 1, true, 'true'], true)
            : false;

        if (! $enabled) {
            return false;
        }

        $now = now();
        $days = explode(',', $prefs['dnd_days'] ?? self::DEFAULTS['shared']['dnd_days']);

        if (! in_array((string) $now->dayOfWeek, $days, true)) {
            return false;
        }

        $start = $prefs['dnd_start_time'] ?? self::DEFAULTS['shared']['dnd_start_time'];
        $end = $prefs['dnd_end_time'] ?? self::DEFAULTS['shared']['dnd_end_time'];

        $currentMinutes = ((int) $now->format('H')) * 60 + (int) $now->format('i');
        [$startH, $startM] = array_map('intval', explode(':', $start));
        [$endH, $endM] = array_map('intval', explode(':', $end));
        $startMinutes = $startH * 60 + $startM;
        $endMinutes = $endH * 60 + $endM;

        if ($startMinutes <= $endMinutes) {
            return $currentMinutes >= $startMinutes && $currentMinutes < $endMinutes;
        }

        return $currentMinutes >= $startMinutes || $currentMinutes < $endMinutes;
    }

    private function castLike(mixed $default, mixed $stored): mixed
    {
        if (is_bool($default)) {
            return in_array($stored, ['1', 1, true, 'true'], true);
        }

        return $stored;
    }
}
