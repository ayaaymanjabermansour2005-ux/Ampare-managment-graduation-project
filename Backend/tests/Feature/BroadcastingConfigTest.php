<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BroadcastingConfigTest extends TestCase
{
    public function test_check_passes_when_reverb_is_fully_and_coherently_configured(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'reverb.apps.apps.0.key' => 'app-key',
            'reverb.apps.apps.0.secret' => 'app-secret',
            'reverb.apps.apps.0.allowed_origins' => ['example.test'],
            'reverb.client.key' => 'app-key',
            'reverb.client.host' => 'example.test',
        ]);

        $exitCode = Artisan::call('broadcasting:check-config');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('متناسقة', Artisan::output());
    }

    public function test_check_fails_in_production_when_vite_key_does_not_match_reverb_key(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config([
            'broadcasting.default' => 'reverb',
            'reverb.apps.apps.0.key' => 'app-key',
            'reverb.apps.apps.0.secret' => 'app-secret',
            'reverb.apps.apps.0.allowed_origins' => ['example.test'],
            'reverb.client.key' => 'a-different-key',
            'reverb.client.host' => 'example.test',
        ]);

        $exitCode = Artisan::call('broadcasting:check-config');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('لا يطابق', Artisan::output());
    }

    public function test_check_fails_in_production_when_broadcast_connection_is_not_reverb(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config(['broadcasting.default' => 'log']);

        $exitCode = Artisan::call('broadcasting:check-config');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('لن تصل فعليًا', Artisan::output());
    }

    public function test_check_fails_in_production_when_reverb_allows_any_origin(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config([
            'broadcasting.default' => 'reverb',
            'reverb.apps.apps.0.key' => 'app-key',
            'reverb.apps.apps.0.secret' => 'app-secret',
            'reverb.apps.apps.0.allowed_origins' => ['*'],
            'reverb.client.key' => 'app-key',
            'reverb.client.host' => 'example.test',
        ]);

        $exitCode = Artisan::call('broadcasting:check-config');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('يسمح بأي أصل', Artisan::output());
    }

    public function test_check_only_warns_outside_production_for_the_same_misconfiguration(): void
    {
        // Non-production environments (local/testing) get a warning, not a
        // failing exit code, so this check can't break local development.
        config(['broadcasting.default' => 'log']);

        $exitCode = Artisan::call('broadcasting:check-config');

        $this->assertSame(0, $exitCode);
    }

    public function test_the_booted_test_environment_passes_because_it_is_not_production(): void
    {
        // Confirms the real, unmodified config (BROADCAST_CONNECTION=null
        // per phpunit.xml, no Reverb credentials in this environment)
        // never fails the check outside production.
        $exitCode = Artisan::call('broadcasting:check-config');

        $this->assertSame(0, $exitCode);
    }
}
