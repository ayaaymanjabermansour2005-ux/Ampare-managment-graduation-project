<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\Neighborhood;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected int $neighborhoodId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);

        $this->neighborhoodId = Neighborhood::factory()->create()->id;
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);
        $admin->givePermissionTo('users.unlock');

        return $admin;
    }

    private function validRegistrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ahmad Ali',
            'email' => 'ahmad@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'neighborhood_id' => $this->neighborhoodId,
            'address' => 'شارع الجلاء، غزة',
        ], $overrides);
    }

    public function test_account_locks_after_max_failed_attempts(): void
    {
        config(['auth.lockout.max_attempts' => 5, 'auth.lockout.durations' => [15]]);

        $user = User::factory()->create(['password' => bcrypt('CorrectPass123!')]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'login' => $user->email,
                'password' => 'WrongPassword',
            ])->assertStatus(422);
        }

        $user->refresh();

        $this->assertTrue($user->isLocked());
        $this->assertSame(0, $user->failed_login_attempts);
    }

    public function test_locked_account_cannot_login_even_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('CorrectPass123!'),
            'locked_until' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'CorrectPass123!',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_existing_session_is_invalidated_once_account_becomes_locked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/auth/me')->assertOk();

        $user->forceFill(['locked_until' => now()->addMinutes(15)])->save();

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertStatus(403);
    }

    public function test_successful_login_resets_failed_attempts_counter(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('CorrectPass123!'),
            'failed_login_attempts' => 3,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'CorrectPass123!',
        ])->assertOk();

        $this->assertSame(0, $user->fresh()->failed_login_attempts);
    }

    public function test_wrong_current_password_is_rejected_with_422(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPass123!'),
        ]);

        $response = $this->actingAs($user)->patchJson('/api/v1/auth/password', [
            'current_password' => 'WrongPass123!',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('CorrectPass123!', $user->fresh()->password));
    }

    public function test_missing_fields_are_rejected_with_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/v1/auth/password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password', 'password']);
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPass123!'),
        ]);

        $response = $this->actingAs($user)->patchJson('/api/v1/auth/password', [
            'current_password' => 'CorrectPass123!',
            'password' => 'NewPass123!',
            'password_confirmation' => 'DifferentPass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_correct_current_password_invalidates_other_sessions_but_keeps_current(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->create([
            'password' => Hash::make('CorrectPass123!'),
        ]);

        $otherSessionId = Str::random(40);

        DB::table('sessions')->insert([
            'id' => $otherSessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'other-device',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $response = $this
            ->withHeader('Referer', 'http://localhost:5173')
            ->actingAs($user)
            ->patchJson('/api/v1/auth/password', [
                'current_password' => 'CorrectPass123!',
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
            ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('NewPass123!', $user->fresh()->password));

        $remainingSessionIds = DB::table('sessions')
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();

        $this->assertNotContains($otherSessionId, $remainingSessionIds);
        $this->assertCount(1, $remainingSessionIds);
    }

    public function test_registration_sends_verification_notification_and_user_is_unverified(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'neighborhood_id' => $this->neighborhoodId,
            'address' => 'شارع الجلاء، غزة',
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $user = User::where('email', 'newuser@example.com')->first();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_login_is_rejected_with_403_when_email_not_verified(): void
    {
        $user = User::factory()->unverified()->create([
            'password' => bcrypt('StrongPass123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'StrongPass123!',
        ]);

        $response->assertStatus(403)->assertJson(['success' => false]);

        $this->assertGuest();
    }

    public function test_valid_signed_link_verifies_email(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($url);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_expired_or_tampered_signature_does_not_verify_email(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $tamperedUrl = str_replace(sha1($user->email), sha1('attacker@example.com'), $url);

        $response = $this->get($tamperedUrl);

        $response->assertRedirect();
        $this->assertStringContainsString('verified=expired', $response->headers->get('Location') ?? '');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_verification_returns_identical_response_for_existing_and_nonexisting_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create(['email' => 'exists2@example.com']);

        $existing = $this->postJson('/api/v1/auth/email/resend', ['email' => 'exists2@example.com']);
        $nonExisting = $this->postJson('/api/v1/auth/email/resend', ['email' => 'ghost@example.com']);

        $existing->assertOk();
        $nonExisting->assertOk();
        $this->assertSame($existing->json('message'), $nonExisting->json('message'));

        Notification::assertSentTo($user, VerifyEmailNotification::class);
        Notification::assertCount(1);
    }

    public function test_already_verified_user_does_not_receive_duplicate_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'verified@example.com']);

        $this->postJson('/api/v1/auth/email/resend', ['email' => 'verified@example.com'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_forgot_password_response_is_identical_for_existing_and_nonexisting_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'exists@example.com']);

        $existingResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'exists@example.com',
        ]);

        $nonExistingResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'does-not-exist@example.com',
        ]);

        $existingResponse->assertOk();
        $nonExistingResponse->assertOk();

        $this->assertSame(
            $existingResponse->json('message'),
            $nonExistingResponse->json('message')
        );

        $this->assertSame(
            $existingResponse->json('success'),
            $nonExistingResponse->json('success')
        );

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        Notification::assertCount(1);
    }

    public function test_forgot_password_validates_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_reset_password_invalidates_all_existing_sessions(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->create();

        DB::table('sessions')->insert([
            [
                'id' => Str::random(40),
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'device-one',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => Str::random(40),
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'device-two',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp,
            ],
        ]);

        $this->assertSame(2, DB::table('sessions')->where('user_id', $user->id)->count());

        $token = Password::broker('users')->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('NewPass123!', $user->fresh()->password));

        $this->assertSame(
            0,
            DB::table('sessions')->where('user_id', $user->id)->count()
        );
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertStatus(422);

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_reset_password_rejects_unregistered_email(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'any-token',
            'email' => 'does-not-exist@example.com',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertStatus(422);
    }

    public function test_registration_creates_user_with_subscriber_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload());

        $response->assertStatus(201);

        $user = User::where('email', 'ahmad@example.com')->first();
        $this->assertTrue($user->hasRole(Role::SUBSCRIBER->value));

        $this->assertDatabaseHas('subscribers', [
            'user_id' => $user->id,
            'neighborhood_id' => $this->neighborhoodId,
        ]);
    }

    public function test_weak_password_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload([
            'email' => 'ahmad2@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload([
            'name' => 'Someone',
            'email' => 'taken@example.com',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_invalid_phone_format_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload([
            'email' => 'ahmad3@example.com',
            'phone' => 'not-a-phone',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_unauthenticated_user_cannot_access_protected_routes_after_registration(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload([
            'email' => 'ahmad4@example.com',
        ]))->assertStatus(201);

        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_admin_can_unlock_a_locked_account(): void
    {
        $admin = $this->makeAdmin();

        $lockedUser = User::factory()->create([
            'locked_until' => now()->addMinutes(30),
            'lockout_count' => 2,
            'failed_login_attempts' => 5,
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$lockedUser->id}/unlock");

        $response->assertOk()->assertJson(['success' => true]);

        $lockedUser->refresh();
        $this->assertFalse($lockedUser->isLocked());
        $this->assertSame(0, $lockedUser->lockout_count);
        $this->assertSame(0, $lockedUser->failed_login_attempts);
    }

    public function test_non_admin_cannot_unlock_accounts(): void
    {
        $subscriber = User::factory()->create();
        $subscriber->assignRole(Role::SUBSCRIBER->value);

        $lockedUser = User::factory()->create(['locked_until' => now()->addMinutes(30)]);

        $this->actingAs($subscriber)
            ->patchJson("/api/v1/users/{$lockedUser->id}/unlock")
            ->assertStatus(403);

        $this->assertTrue($lockedUser->fresh()->isLocked());
    }

    public function test_unlock_route_requires_authentication(): void
    {
        $lockedUser = User::factory()->create(['locked_until' => now()->addMinutes(30)]);

        $this->patchJson("/api/v1/users/{$lockedUser->id}/unlock")
            ->assertStatus(401);
    }

    public function test_admin_can_list_locked_accounts(): void
    {
        $admin = $this->makeAdmin();

        User::factory()->create(['locked_until' => now()->addMinutes(30)]);
        User::factory()->create(['locked_until' => null]);

        $response = $this->actingAs($admin)->getJson('/api/v1/users/locked');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_locked_accounts_list_eager_loads_roles_and_does_not_n_plus_one(): void
    {
        // تدقيق شامل — الجولة السادسة: AccountLockoutService::lockedAccounts()
        // كانت بدون with('roles', ...)، فـ UserResource كانت تطلق استعلام
        // roles/permissions منفصل لكل مستخدم مقفول (N+1).
        $admin = $this->makeAdmin();

        User::factory()->count(3)->create(['locked_until' => now()->addMinutes(30)])
            ->each(fn (User $u) => $u->assignRole(Role::SUBSCRIBER->value));

        DB::enableQueryLog();
        $this->actingAs($admin)->getJson('/api/v1/users/locked')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::flushQueryLog();

        // ثابت بغض النظر عن عدد الحسابات المقفولة — لا استعلام إضافي لكل مستخدم.
        $this->assertLessThan(15, $queryCount);
    }
}
