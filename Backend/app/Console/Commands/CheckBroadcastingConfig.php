<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * PROD-01: production readiness check for the full broadcasting chain
 * (Event/Notification → BROADCAST_CONNECTION → Reverb server → Echo
 * frontend). Intended to run during deployment/CI so a misconfigured
 * environment fails loudly instead of silently shipping with real-time
 * features that look wired up but never actually deliver anything.
 */
class CheckBroadcastingConfig extends Command
{
    protected $signature = 'broadcasting:check-config';

    protected $description = 'التحقق من تناسق إعدادات البث اللحظي (Reverb) قبل النشر — يفشل بوضوح إن كانت الإعدادات غير متطابقة.';

    public function handle(): int
    {
        $problems = [];
        $warnings = [];

        $connection = config('broadcasting.default');

        if (! app()->isProduction()) {
            $this->info('بيئة التطبيق الحالية: '.app()->environment()." — بث محرك: {$connection}.");
            $this->line('هذا الفحص إلزامي فقط في الإنتاج؛ في بيئات أخرى هو استعلامي فقط.');
        }

        if ($connection !== 'reverb') {
            $message = "BROADCAST_CONNECTION هو '{$connection}' — الإشعارات اللحظية عبر Echo/Reverb لن تصل فعليًا للمتصفح (القناة 'log' تكتب فقط في ملف السجل، و'null' لا تفعل شيئًا).";

            if (app()->isProduction()) {
                $problems[] = $message;
            } else {
                $warnings[] = $message;
            }
        }

        $appKey = config('reverb.apps.apps.0.key');
        $appSecret = config('reverb.apps.apps.0.secret');
        $viteKey = config('reverb.client.key');
        $viteHost = config('reverb.client.host');

        // Only checked when BROADCAST_CONNECTION is actually reverb — a
        // deployment intentionally using a different broadcaster (or none)
        // shouldn't be flagged for Reverb-specific credentials it doesn't need.
        if ($connection === 'reverb') {
            $credentialProblems = [];

            if (! $appKey || ! $appSecret) {
                $credentialProblems[] = 'REVERB_APP_KEY أو REVERB_APP_SECRET غير مضبوطة رغم أن BROADCAST_CONNECTION=reverb.';
            }

            if (! $viteKey) {
                $credentialProblems[] = 'VITE_REVERB_APP_KEY غير مضبوطة — واجهة Vue (resources/js/plugins/echo.js) لن تستطيع الاتصال بخادم Reverb.';
            } elseif ($appKey && $viteKey !== $appKey) {
                $credentialProblems[] = 'VITE_REVERB_APP_KEY لا يطابق REVERB_APP_KEY — يجب أن يكونا متطابقين (المفتاح عام وليس سريًا، بعكس REVERB_APP_SECRET).';
            }

            if (! $viteHost) {
                $warnings[] = 'VITE_REVERB_HOST غير مضبوطة — سيتم استخدام host الصفحة الحالية كافتراضي.';
            }

            // Missing/mismatched credentials are only a hard failure in
            // production — a fresh local checkout without `reverb:install`
            // run yet is an expected, non-broken state.
            if (app()->isProduction()) {
                array_push($problems, ...$credentialProblems);
            } else {
                array_push($warnings, ...$credentialProblems);
            }
        }

        $allowedOrigins = config('reverb.apps.apps.0.allowed_origins', []);

        if (app()->isProduction() && in_array('*', $allowedOrigins, true)) {
            $problems[] = "REVERB_ALLOWED_ORIGINS يسمح بأي أصل ('*') في الإنتاج — يجب تحديد النطاقات المسموح بها صراحةً.";
        }

        foreach ($warnings as $warning) {
            $this->warn("⚠ {$warning}");
        }

        if ($problems === []) {
            $this->info('✓ إعدادات البث اللحظي متناسقة.');

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->error("✗ {$problem}");
        }

        return self::FAILURE;
    }
}
