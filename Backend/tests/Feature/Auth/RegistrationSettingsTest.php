<?php

namespace Tests\Feature\Auth;

use App\Enums\Role as RoleEnum;
use App\Enums\UserStatus;
use App\Models\Neighborhood;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function validPayload(): array
    {
        $neighborhood = Neighborhood::factory()->create();

        return [
            'name' => 'مستخدم تجريبي',
            'email' => 'test@example.com',
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
            'neighborhood_id' => $neighborhood->id,
            'address' => 'عنوان تجريبي',
        ];
    }

    public function test_registration_is_blocked_when_disabled_by_admin(): void
    {
        Setting::set('allow_subscriber_registration', '0');

        $this->postJson('/api/v1/auth/register', $this->validPayload())
            ->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_succeeds_when_enabled_by_default(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload())
            ->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_is_auto_verified_when_email_verification_disabled(): void
    {
        Setting::set('require_email_verification', '0');

        $this->postJson('/api/v1/auth/register', $this->validPayload())->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_user_can_login_without_verifying_email_when_setting_disabled(): void
    {
        Setting::set('require_email_verification', '0');

        $payload = $this->validPayload();
        $this->postJson('/api/v1/auth/register', $payload)->assertStatus(201);

        $this->postJson('/api/v1/auth/login', [
            'login' => $payload['email'],
            'password' => $payload['password'],
        ])->assertOk();
    }

    public function test_account_starts_as_pending_review_when_setting_enabled(): void
    {
        Setting::set('review_new_accounts_before_activation', '1');

        $this->postJson('/api/v1/auth/register', $this->validPayload())->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertSame(UserStatus::PendingReview, $user->status);
    }

    public function test_pending_review_account_cannot_login_with_clear_message(): void
    {
        Setting::set('review_new_accounts_before_activation', '1');
        Setting::set('require_email_verification', '0');

        $payload = $this->validPayload();
        $this->postJson('/api/v1/auth/register', $payload)->assertStatus(201);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $payload['email'],
            'password' => $payload['password'],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('مراجعة الإدارة', $response->json('errors.login.0'));
    }

    public function test_admin_can_activate_pending_review_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        $user = User::factory()->create(['status' => UserStatus::PendingReview]);
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$user->id}", ['status' => 'active'])
            ->assertOk();

        $this->assertSame(UserStatus::Active, $user->fresh()->status);
    }

    public function test_normal_registration_flow_unaffected_when_all_settings_default(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload())->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNull($user->email_verified_at);
        $this->assertSame(UserStatus::Active, $user->status);
    }
}
