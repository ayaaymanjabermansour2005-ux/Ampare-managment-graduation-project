<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_response_carries_baseline_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_api_response_also_carries_security_headers(): void
    {
        $response = $this->getJson('/api/v1/platform-identity');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_content_security_policy_allows_the_applications_own_origin(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString('fonts.googleapis.com', $csp);
        $this->assertStringContainsString('fonts.gstatic.com', $csp);
        $this->assertStringContainsString('tile.openstreetmap.org', $csp);
    }

    public function test_hsts_is_never_sent_over_plain_http_in_testing(): void
    {
        // The test/local environment is never "production", and this
        // request is not secure — HSTS must not be sent either way.
        $response = $this->get('/');

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_sent_only_in_production_over_https(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('https://example.test/', ['HTTPS' => 'on']);

        $response->assertHeader('Strict-Transport-Security');
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
        $this->assertStringContainsString('includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }
}
