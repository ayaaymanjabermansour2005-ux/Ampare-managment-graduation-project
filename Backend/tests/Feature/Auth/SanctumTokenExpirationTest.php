<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumTokenExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_expiration_config_has_a_safe_non_null_default(): void
    {
        // AUTH-01: config/sanctum.php must never resolve to null (= tokens
        // that never expire) once SANCTUM_TOKEN_EXPIRATION is set, as it is
        // in .env.example and in phpunit.xml.
        $this->assertNotNull(config('sanctum.expiration'));
        $this->assertSame(20160, (int) config('sanctum.expiration'));
    }

    public function test_a_bearer_token_older_than_the_expiration_window_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device');

        // Backdate the token past the configured expiration window
        // (20160 minutes = 14 days) so it is provably expired.
        $token->accessToken->forceFill([
            'created_at' => now()->subMinutes((int) config('sanctum.expiration') + 1),
        ])->save();

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_a_bearer_token_within_the_expiration_window_is_accepted(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
    }
}
