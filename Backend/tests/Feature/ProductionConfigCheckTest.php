<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * SEC-002/SEC-003: proves `app:check-production` actually enforces
 * APP_DEBUG=false and SESSION_SECURE_COOKIE=true in production, and never
 * blocks non-production environments — mirroring BroadcastingConfigTest.php's
 * exact structure for the sibling PROD-01 check.
 */
class ProductionConfigCheckTest extends TestCase
{
    public function test_check_passes_in_production_when_debug_is_off_and_session_cookie_is_secure(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config(['app.debug' => false, 'session.secure' => true]);

        $exitCode = Artisan::call('app:check-production');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('سليمة', Artisan::output());
    }

    public function test_check_fails_in_production_when_debug_is_on(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config(['app.debug' => true, 'session.secure' => true]);

        $exitCode = Artisan::call('app:check-production');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('APP_DEBUG=true', Artisan::output());
    }

    public function test_check_fails_in_production_when_session_cookie_is_not_secure(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config(['app.debug' => false, 'session.secure' => false]);

        $exitCode = Artisan::call('app:check-production');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE', Artisan::output());
    }

    public function test_check_fails_in_production_when_session_cookie_is_unset(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config(['app.debug' => false, 'session.secure' => null]);

        $exitCode = Artisan::call('app:check-production');

        $this->assertSame(1, $exitCode);
    }

    public function test_check_reports_both_problems_at_once_when_both_are_misconfigured(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config(['app.debug' => true, 'session.secure' => false]);

        $exitCode = Artisan::call('app:check-production');

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('APP_DEBUG=true', $output);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE', $output);
    }

    public function test_check_only_informs_outside_production_for_the_same_misconfiguration(): void
    {
        // Non-production environments (local/testing) never fail this
        // check, so it can't break local development — mirrors
        // CheckBroadcastingConfig's identical, established behavior.
        config(['app.debug' => true, 'session.secure' => false]);

        $exitCode = Artisan::call('app:check-production');

        $this->assertSame(0, $exitCode);
    }

    public function test_the_booted_test_environment_passes_because_it_is_not_production(): void
    {
        // Confirms the real, unmodified config in this environment
        // (APP_ENV=testing per phpunit.xml) never fails the check outside
        // production, without manually overriding config().
        $exitCode = Artisan::call('app:check-production');

        $this->assertSame(0, $exitCode);
    }
}
