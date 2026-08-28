<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * SEC-002/SEC-003: production readiness check for the two config values the
 * original audit flagged as unenforced — `APP_DEBUG` and
 * `SESSION_SECURE_COOKIE`. Neither has any runtime guard today; if
 * production is ever deployed with `APP_DEBUG=true`, any unhandled 500
 * leaks exception class/file/line to any caller, and if
 * `SESSION_SECURE_COOKIE` isn't explicitly `true`, the session cookie could
 * theoretically transit over a downgraded HTTP connection. Mirrors
 * `CheckBroadcastingConfig`'s established pattern (PROD-01): hard-fails only
 * in production, informational elsewhere, never prints the actual value of
 * anything secret (only booleans are checked here, so nothing secret is
 * ever in scope to leak).
 */
class CheckProductionConfig extends Command
{
    protected $signature = 'app:check-production';

    protected $description = 'التحقق من إعدادات الإنتاج الحرجة (APP_DEBUG, SESSION_SECURE_COOKIE) قبل النشر — يفشل بوضوح إن كانت غير آمنة.';

    public function handle(): int
    {
        $problems = [];

        if (! app()->isProduction()) {
            $this->info('بيئة التطبيق الحالية: '.app()->environment().' — هذا الفحص إلزامي فقط في الإنتاج؛ في بيئات أخرى هو استعلامي فقط.');
        }

        $debug = (bool) config('app.debug');
        $sessionSecure = config('session.secure');

        if (app()->isProduction() && $debug) {
            $problems[] = 'APP_DEBUG=true في بيئة الإنتاج — أي خطأ 500 غير متوقَّع سيُظهر تفاصيل الاستثناء (اسم الكلاس، الملف، رقم السطر) لأي طرف يستقبل الاستجابة.';
        } elseif (! app()->isProduction() && $debug) {
            $this->line('APP_DEBUG=true — مقبول خارج بيئة الإنتاج.');
        }

        if (app()->isProduction() && $sessionSecure !== true) {
            $problems[] = 'SESSION_SECURE_COOKIE غير مضبوطة على true في بيئة الإنتاج — كوكي الجلسة يمكن نظريًا أن ينتقل عبر اتصال HTTP غير مشفّر قبل أن يُفعَّل HSTS.';
        } elseif (! app()->isProduction() && $sessionSecure !== true) {
            $this->line('SESSION_SECURE_COOKIE ليست true — مقبول خارج بيئة الإنتاج (HTTP محلي).');
        }

        if ($problems === []) {
            $this->info('✓ إعدادات الإنتاج الحرجة سليمة.');

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->error("✗ {$problem}");
        }

        return self::FAILURE;
    }
}
