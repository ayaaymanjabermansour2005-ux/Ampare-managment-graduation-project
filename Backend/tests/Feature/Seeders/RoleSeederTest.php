<?php

namespace Tests\Feature\Seeders;

use App\Enums\Role as RoleEnum;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * SEC-005: RoleSeeder used to unconditionally create an admin account with
 * the hardcoded, predictable password 'Password123!' — no environment guard.
 * These tests prove the fix's actual runtime behavior, not just the presence
 * of the guard clause in source.
 */
class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_all_role_records_regardless_of_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        (new RoleSeeder)->run();

        foreach (RoleEnum::cases() as $role) {
            $this->assertTrue(
                Role::where('name', $role->value)->where('guard_name', 'sanctum')->exists(),
                "Role '{$role->value}' must exist regardless of environment — it is structural RBAC data, not a seeded account."
            );
        }
    }

    public function test_does_not_create_admin_account_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        (new RoleSeeder)->run();

        $this->assertDatabaseMissing('users', ['email' => 'admin@ampare.test']);
        $this->assertSame(0, User::count(), 'No user of any kind should be created by RoleSeeder in production.');
    }

    public function test_creates_admin_account_outside_production(): void
    {
        // The test environment (APP_ENV=testing per phpunit.xml) is not
        // 'production', so this exercises the same path local dev uses.
        (new RoleSeeder)->run();

        $admin = User::where('email', 'admin@ampare.test')->first();

        $this->assertNotNull($admin, 'RoleSeeder must still create the admin account outside production.');
        $this->assertTrue($admin->hasRole(RoleEnum::ADMIN->value));
        $this->assertSame(UserStatus::Active, $admin->status);
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_admin_password_is_not_a_hardcoded_predictable_value_when_no_env_password_is_set(): void
    {
        config(['seeding.dev_admin_password' => null]);

        (new RoleSeeder)->run();

        $admin = User::where('email', 'admin@ampare.test')->firstOrFail();

        $this->assertFalse(
            Hash::check('Password123!', $admin->password),
            'The old hardcoded literal must never be the resulting password.'
        );
        $this->assertFalse(
            Hash::check('password', $admin->password),
            'The generated password must not coincidentally be the other commonly-hardcoded literal.'
        );
    }

    public function test_admin_password_honors_dev_admin_password_config_when_set(): void
    {
        config(['seeding.dev_admin_password' => 'SomeStrongLocalDevPass!42']);

        (new RoleSeeder)->run();

        $admin = User::where('email', 'admin@ampare.test')->firstOrFail();

        $this->assertTrue(Hash::check('SomeStrongLocalDevPass!42', $admin->password));
    }

    public function test_admin_email_honors_dev_admin_email_config_when_set(): void
    {
        config(['seeding.dev_admin_email' => 'custom-dev-admin@ampare.test']);

        (new RoleSeeder)->run();

        $this->assertDatabaseHas('users', ['email' => 'custom-dev-admin@ampare.test']);
        $this->assertDatabaseMissing('users', ['email' => 'admin@ampare.test']);
    }

    public function test_seeder_is_idempotent_and_never_overwrites_an_existing_admin_password(): void
    {
        config(['seeding.dev_admin_password' => 'FirstRunPassword!1']);
        (new RoleSeeder)->run();

        $admin = User::where('email', 'admin@ampare.test')->firstOrFail();
        $originalHash = $admin->password;

        // Simulate a second seed run with a *different* configured password —
        // an already-existing local admin account must not be silently
        // reset to a new password on every re-seed.
        config(['seeding.dev_admin_password' => 'SecondRunPassword!2']);
        (new RoleSeeder)->run();

        $admin->refresh();

        $this->assertSame($originalHash, $admin->password);
        $this->assertTrue(Hash::check('FirstRunPassword!1', $admin->password));
        $this->assertSame(1, User::where('email', 'admin@ampare.test')->count());
    }
}
