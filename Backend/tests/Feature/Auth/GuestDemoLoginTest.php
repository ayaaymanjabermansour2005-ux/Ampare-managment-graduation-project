<?php

namespace Tests\Feature\Auth;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GuestDemoLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeDemoUser(string $email, string $role): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'is_guest_demo' => true,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_guest_can_login_as_demo_subscriber(): void
    {
        $demo = $this->makeDemoUser('demo-subscriber@ampare.test', RoleEnum::SUBSCRIBER->value);

        $response = $this->postJson('/api/v1/public/guest-login', ['role' => 'subscriber']);

        $response->assertOk();
        $this->assertSame($demo->id, $response->json('data.id'));
        $this->assertAuthenticatedAs($demo, 'web');
    }

    public function test_guest_can_login_as_demo_owner(): void
    {
        $demo = $this->makeDemoUser('demo-owner@ampare.test', RoleEnum::GENERATOR_OWNER->value);

        $response = $this->postJson('/api/v1/public/guest-login', ['role' => 'owner']);

        $response->assertOk();
        $this->assertSame($demo->id, $response->json('data.id'));
        $this->assertAuthenticatedAs($demo, 'web');
    }

    public function test_login_fails_when_demo_account_missing(): void
    {
        $response = $this->postJson('/api/v1/public/guest-login', ['role' => 'subscriber']);

        $response->assertStatus(422)->assertJsonValidationErrors(['role']);
        $this->assertGuest('web');
    }

    public function test_invalid_role_value_is_rejected(): void
    {
        $this->postJson('/api/v1/public/guest-login', ['role' => 'admin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_guest_login_is_rate_limited(): void
    {
        Cache::flush();

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/v1/public/guest-login', ['role' => 'subscriber']);
        }

        $this->postJson('/api/v1/public/guest-login', ['role' => 'subscriber'])->assertStatus(429);
    }
}
