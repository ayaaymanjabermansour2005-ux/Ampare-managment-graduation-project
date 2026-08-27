<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettingService
{
    private const KEYS = [
        'site_name',
        'logo_url',
        'default_language',
        'default_currency',
        'default_ampere_price',
        'platform_fee_percentage',
        'tax_percentage',
        'maps_api_key',
        'sms_api_key',
        'whatsapp_api_key',
        'payment_gateway_key',
        'allow_subscriber_registration',
        'require_email_verification',
        'review_new_accounts_before_activation',
        'favicon_url',
        'email_logo_url',
        'invoice_logo_url',
        'about_page_content',
        'privacy_policy_content',
        'terms_content',
        'contact_page_content',
        'maintenance_mode_enabled',
        'maintenance_message',
        'fallback_usd_ils_rate',
    ];

    private const SENSITIVE_KEYS = [
        'maps_api_key',
        'sms_api_key',
        'whatsapp_api_key',
        'payment_gateway_key',
    ];

    private const NUMERIC_KEYS = [
        'default_ampere_price' => ['min' => 0],
        'platform_fee_percentage' => ['min' => 0, 'max' => 100],
        'tax_percentage' => ['min' => 0, 'max' => 100],
        'fallback_usd_ils_rate' => ['min' => 0.01],
    ];

    private const BOOLEAN_KEYS_DEFAULTS = [
        'allow_subscriber_registration' => '1',
        'require_email_verification' => '1',
        'review_new_accounts_before_activation' => '0',
        'maintenance_mode_enabled' => '0',
    ];

    private const PUBLIC_KEYS = [
        'site_name',
        'logo_url',
        'favicon_url',
        'about_page_content',
        'privacy_policy_content',
        'terms_content',
        'contact_page_content',
    ];

    /**
     * @return Collection<string, mixed>
     */
    public function all(): Collection
    {
        return collect(self::KEYS)->mapWithKeys(function ($key) {
            if (in_array($key, self::SENSITIVE_KEYS, true)) {
                $value = Setting::get($key);

                return [$key => [
                    'is_set' => filled($value),
                    'masked_value' => $value ? '••••••••'.substr($value, -4) : null,
                ]];
            }

            if (array_key_exists($key, self::BOOLEAN_KEYS_DEFAULTS)) {
                $default = self::BOOLEAN_KEYS_DEFAULTS[$key];

                return [$key => in_array(Setting::get($key, $default), ['1', 1, true, 'true'], true)];
            }

            return [$key => Setting::get($key)];
        });
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function update(array $settings): void
    {
        $toUpdate = array_intersect_key($settings, array_flip(self::KEYS));

        foreach (self::NUMERIC_KEYS as $key => $bounds) {
            if (! array_key_exists($key, $toUpdate)) {
                continue;
            }

            if (! is_numeric($toUpdate[$key])) {
                throw ValidationException::withMessages([
                    "settings.{$key}" => ['يجب أن تكون هذه القيمة رقمًا.'],
                ]);
            }

            $numeric = (float) $toUpdate[$key];

            if (
                $numeric < $bounds['min'] ||
                (isset($bounds['max']) && $numeric > $bounds['max'])
            ) {
                throw ValidationException::withMessages([
                    "settings.{$key}" => ['القيمة خارج النطاق المسموح.'],
                ]);
            }
        }

        foreach (self::BOOLEAN_KEYS_DEFAULTS as $key => $default) {
            if (! array_key_exists($key, $toUpdate)) {
                continue;
            }

            if (! is_bool($toUpdate[$key]) && ! in_array($toUpdate[$key], ['0', '1', 0, 1, 'true', 'false'], true)) {
                throw ValidationException::withMessages([
                    "settings.{$key}" => ['يجب أن تكون هذه القيمة true أو false.'],
                ]);
            }

            $toUpdate[$key] = in_array($toUpdate[$key], [true, '1', 1, 'true'], true) ? '1' : '0';
        }

        DB::transaction(function () use ($toUpdate) {
            foreach ($toUpdate as $key => $value) {
                Setting::set($key, $value);
            }
        });
    }

    /**
     * @return Collection<string, mixed>
     */
    public function public(): Collection
    {
        return collect(self::PUBLIC_KEYS)->mapWithKeys(
            fn ($key) => [$key => Setting::get($key)]
        );
    }
}
