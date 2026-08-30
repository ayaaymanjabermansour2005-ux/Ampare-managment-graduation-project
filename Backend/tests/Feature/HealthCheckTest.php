<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * DEVOPS-005: proves `/up` now actually verifies dependency connectivity
 * (database, queue, Reverb broadcasting) instead of only confirming the app
 * booted — via App\Listeners\CheckApplicationDependenciesHealth, which
 * Laravel invokes through the DiagnosingHealth event dispatched by the
 * framework's own `/up` route (see ApplicationBuilder::withRouting()).
 */
class HealthCheckTest extends TestCase
{
    public function test_up_returns_200_when_all_configured_dependencies_are_reachable(): void
    {
        // Default test env: sync queue (no external broker) + no broadcasting
        // configured as reverb, so only the real database connection is checked.
        config(['broadcasting.default' => 'log']);

        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_up_returns_500_when_reverb_is_configured_but_unreachable(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            // A port nothing listens on in the test environment — guarantees
            // a real, deterministic connection failure without mocking.
            'broadcasting.connections.reverb.options.port' => 6, // reserved/unassigned TCP port
        ]);

        $response = $this->get('/up');

        $response->assertStatus(500);
    }

    public function test_up_ignores_broadcasting_when_it_is_not_configured_as_reverb(): void
    {
        config(['broadcasting.default' => 'log']);

        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_up_skips_the_queue_check_for_the_sync_driver(): void
    {
        config(['queue.default' => 'sync', 'broadcasting.default' => 'log']);

        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
