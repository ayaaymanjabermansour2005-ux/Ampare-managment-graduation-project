<?php

namespace Tests\Feature\Seeders;

use App\Models\OwnerApplication;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * SEC-006: DatabaseSeeder::seedCoreData() used to unconditionally create 7
 * demo accounts (owner1/2, subscriber1/2/3, technician1/2) sharing the same
 * hardcoded password 'password', with no environment guard. Two additional
 * OwnerApplication demo rows (found while verifying this finding, same root
 * cause) had the identical issue and are covered here too.
 *
 * seedCoreData() is a private method with no public entry point other than
 * the full DatabaseSeeder::run() chain (which also runs PlatformUsersSeeder,
 * a much heavier fixture). Reflection is used here to exercise seedCoreData()
 * directly, matching how the original code invokes it (Model::unguarded(...)).
 *
 * NOTE: seedCoreData() is only invoked once per successful run in this file
 * (test_seed_core_data_creates_demo_accounts_with_unique_non_hardcoded_passwords_outside_production
 * covers every "outside production" assertion together), not once per test
 * method. This is deliberate: the method contains a separate, pre-existing,
 * unrelated bug (`MeterReading::firstOrCreate(['subscription_id' => 1, ...])`
 * hardcodes id=1 instead of the just-created Subscription's real id) that
 * surfaces as a foreign-key violation on any second invocation sharing the
 * same DB connection, because MySQL auto-increment counters are not rolled
 * back by RefreshDatabase's per-test transaction. That bug is out of scope
 * for SEC-006 (unrelated to credentials) and is documented separately in the
 * audit report rather than fixed here, per the "one problem at a time" rule.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private const DEMO_USER_EMAILS = [
        'owner1@ampare.test',
        'owner2@ampare.test',
        'subscriber1@ampare.test',
        'subscriber2@ampare.test',
        'subscriber3@ampare.test',
        'technician1@ampare.test',
        'technician2@ampare.test',
    ];

    public function test_seed_core_data_throws_and_creates_nothing_in_production(): void
    {
        (new RoleSeeder)->run();
        $this->app->detectEnvironment(fn () => 'production');

        $thrown = false;
        try {
            $this->callSeedCoreData();
        } catch (RuntimeException $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown, 'seedCoreData() must throw a RuntimeException in production.');
        foreach (self::DEMO_USER_EMAILS as $email) {
            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    public function test_seed_core_data_creates_demo_accounts_with_unique_non_hardcoded_passwords_outside_production(): void
    {
        (new RoleSeeder)->run();
        $this->callSeedCoreData();

        foreach (self::DEMO_USER_EMAILS as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);

            $user = User::where('email', $email)->firstOrFail();
            $this->assertFalse(
                Hash::check('password', $user->password),
                "{$email} must not use the old shared hardcoded literal 'password'."
            );
        }

        foreach (['owner-applicant-pending@example.test', 'owner-applicant-approved@example.test'] as $email) {
            $application = OwnerApplication::where('email', $email)->firstOrFail();
            $this->assertFalse(
                Hash::check('password', $application->password),
                "{$email} (OwnerApplication) must not use the old hardcoded literal 'password'."
            );
        }
    }

    public function test_demo_password_helper_generates_a_different_strong_value_every_call(): void
    {
        $seeder = new DatabaseSeeder;
        $method = new ReflectionMethod(DatabaseSeeder::class, 'demoPassword');
        $method->setAccessible(true);

        $passwords = [];
        for ($i = 0; $i < 10; $i++) {
            $passwords[] = $method->invoke($seeder, "account-{$i}@example.test");
        }

        $this->assertCount(
            10,
            array_unique($passwords),
            'Every generated demo password must be unique — no account may share a password with another.'
        );

        foreach ($passwords as $password) {
            $this->assertGreaterThanOrEqual(16, strlen($password));
            $this->assertNotSame('password', $password);
            $this->assertNotSame('Password123!', $password);
        }
    }

    private function callSeedCoreData(): void
    {
        $seeder = new DatabaseSeeder;
        $method = new ReflectionMethod(DatabaseSeeder::class, 'seedCoreData');
        $method->setAccessible(true);

        Model::unguarded(fn () => $method->invoke($seeder));
    }
}
