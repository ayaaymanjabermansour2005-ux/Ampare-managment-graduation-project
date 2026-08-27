<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_session(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertAuthenticated();
    }

    public function test_authenticated_user_can_access_me(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
