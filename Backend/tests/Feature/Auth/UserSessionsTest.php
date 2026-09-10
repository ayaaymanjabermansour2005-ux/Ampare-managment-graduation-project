<?php

namespace Tests\Feature\Auth;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        config(['session.driver' => 'database']);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        return $user;
    }

    private function seedSession(string $id, int $userId, ?string $ip = '1.2.3.4'): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);
    }

    public function test_user_can_list_own_sessions(): void
    {
        $user = $this->makeUser();
        $this->seedSession('session-a', $user->id);
        $this->seedSession('session-b', $user->id);

        $response = $this->actingAs($user)->getJson('/api/v1/auth/sessions');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.sessions'));
        $this->assertTrue($response->json('data.is_supported'));
    }

    public function test_sessions_are_scoped_to_current_user_only(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $this->seedSession('session-mine', $user->id);
        $this->seedSession('session-other', $other->id);

        $response = $this->actingAs($user)->getJson('/api/v1/auth/sessions');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.sessions'));
        $this->assertSame('session-mine', $response->json('data.sessions.0.id'));
    }

    /**
     * FIX (تدقيق شامل — E5): is_supported يميّز الآن "لا جلسات أخرى" الحقيقية
     * عن "الميزة غير مدعومة بهذه البيئة" (SESSION_DRIVER != database) —
     * كانت الحالتان تُعادان كمصفوفة فارغة بلا أي تمييز.
     */
    public function test_sessions_endpoint_reports_unsupported_when_session_driver_is_not_database(): void
    {
        config(['session.driver' => 'file']);
        $user = $this->makeUser();

        $response = $this->actingAs($user)->getJson('/api/v1/auth/sessions');

        $response->assertOk();
        $this->assertSame([], $response->json('data.sessions'));
        $this->assertFalse($response->json('data.is_supported'));
    }

    public function test_user_can_revoke_own_session(): void
    {
        $user = $this->makeUser();
        $this->seedSession('session-to-revoke', $user->id);

        $this->actingAs($user)
            ->deleteJson('/api/v1/auth/sessions/session-to-revoke')
            ->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'session-to-revoke']);
    }

    public function test_user_cannot_revoke_another_users_session(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $this->seedSession('session-other', $other->id);

        $this->actingAs($user)
            ->deleteJson('/api/v1/auth/sessions/session-other')
            ->assertStatus(404);

        $this->assertDatabaseHas('sessions', ['id' => 'session-other']);
    }

    public function test_revoking_nonexistent_session_returns_404(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->deleteJson('/api/v1/auth/sessions/does-not-exist')
            ->assertStatus(404);
    }

    public function test_user_can_view_own_login_log(): void
    {
        $user = $this->makeUser();

        activity()
            ->performedOn($user)
            ->withProperties(['ip' => '9.9.9.9'])
            ->log('login_succeeded');

        $response = $this->actingAs($user)->getJson('/api/v1/auth/login-log');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data.data')));
    }

    public function test_login_log_does_not_leak_other_users_entries(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        activity()->performedOn($other)->withProperties(['ip' => '1.1.1.1'])->log('login_succeeded');

        $response = $this->actingAs($user)->getJson('/api/v1/auth/login-log');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.data'));
    }

    public function test_unauthenticated_user_cannot_access_sessions(): void
    {
        $this->getJson('/api/v1/auth/sessions')->assertStatus(401);
    }
}
