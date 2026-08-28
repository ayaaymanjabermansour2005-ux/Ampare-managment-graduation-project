<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * DEVOPS-004: spatie/laravel-flare is IMPLEMENTED (installed, configured
 * with a project-specific censor list, auto-registered via package
 * discovery) but NOT CONFIGURED (no real FLARE_KEY in any environment this
 * session has access to) and therefore NOT VERIFIED end-to-end against a
 * real Flare account. This test locks in the one thing that genuinely is
 * verified: that the package fails safely — refusing to send anything, not
 * crashing, not attempting a real network call — when no key is present,
 * confirmed directly via `php artisan flare:test --errors` before this test
 * was written, not assumed from reading the package's source alone.
 */
class FlareIntegrationTest extends TestCase
{
    public function test_flare_test_command_fails_safely_with_no_configured_key(): void
    {
        config(['flare.key' => null]);

        $exitCode = Artisan::call('flare:test', ['--errors' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Flare key not specified', Artisan::output());
    }

    public function test_flare_config_censors_this_projects_own_sensitive_fields(): void
    {
        $censoredFields = config('flare.censor.body_fields');

        foreach ([
            'password',
            'password_confirmation',
            'card_number',
            'card_holder_name',
            'expiry_month',
            'expiry_year',
            'cvv',
            'account_number',
            'token',
        ] as $field) {
            $this->assertContains(
                $field,
                $censoredFields,
                "Expected '{$field}' to be in flare.censor.body_fields so it's never sent to the error tracker."
            );
        }
    }
}
