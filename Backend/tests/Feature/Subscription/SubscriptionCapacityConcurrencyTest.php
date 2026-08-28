<?php

namespace Tests\Feature\Subscription;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BUG-001 follow-up: SubscriptionTest.php's Pending→Active tests prove the
 * *business logic* (capacity/duplicate re-check now runs, correctly rejects
 * oversell) — but those run under RefreshDatabase, which wraps every test in
 * an uncommitted outer transaction. That makes it impossible to prove the
 * actual *concurrency* guarantee (that Generator::lockForUpdate() genuinely
 * blocks a second, truly independent database connection) — a second
 * connection cannot see rows created inside a transaction that hasn't
 * committed yet, on any isolation level.
 *
 * This file deliberately does NOT use RefreshDatabase. It works against the
 * real, already-migrated test database with genuinely committed rows, and
 * manually cleans up what it creates in tearDown(). This is the only way to
 * open a second, real, independent DB connection to the same database and
 * prove the row lock is real — not just trust that DB::transaction() +
 * lockForUpdate() "should" work because it's a standard Laravel/MySQL
 * pattern already used elsewhere in this codebase.
 */
class SubscriptionCapacityConcurrencyTest extends TestCase
{
    private array $createdSubscriptionIds = [];

    private array $createdGeneratorIds = [];

    private array $createdMeterIds = [];

    private array $createdSubscriberIds = [];

    private array $createdUserIds = [];

    private bool $seededRolesInSetUp = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Migrations are already applied by every other RefreshDatabase test
        // in this suite; `migrate` is a safe no-op if the schema is current.
        Artisan::call('migrate', ['--force' => true]);

        if (\Spatie\Permission\Models\Role::query()->count() === 0) {
            $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
            $this->seededRolesInSetUp = true;
        }
    }

    protected function tearDown(): void
    {
        // No RefreshDatabase rollback here — everything created in this test
        // is real, committed data, so it must be deleted explicitly.
        Subscription::whereIn('id', $this->createdSubscriptionIds)->forceDelete();
        Generator::whereIn('id', $this->createdGeneratorIds)->forceDelete();
        SubscriberMeter::whereIn('id', $this->createdMeterIds)->forceDelete();
        Subscriber::whereIn('id', $this->createdSubscriberIds)->forceDelete();
        User::whereIn('id', $this->createdUserIds)->forceDelete();

        // If this test happened to be the one that ran RoleSeeder (only when
        // Role::count() === 0 at setUp time), it also created a real,
        // permanently-committed admin account via RoleSeeder::seedLocalAdmin()
        // — one this class doesn't track in $createdUserIds. Left uncleaned,
        // it silently corrupts any later RefreshDatabase test in the same
        // process that asserts against a clean admin@ampare.test state (e.g.
        // RoleSeederTest), since RoleSeeder::seedLocalAdmin() uses
        // firstOrCreate() and will not touch an already-existing row.
        if ($this->seededRolesInSetUp) {
            User::where('email', config('seeding.dev_admin_email', 'admin@ampare.test'))->forceDelete();
        }

        parent::tearDown();
    }

    public function test_a_second_real_connection_is_blocked_from_the_generator_row_while_an_approval_transaction_holds_its_lock(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $this->createdUserIds[] = $owner->id;

        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'operating_schedule' => '24h', 'capacity_kw' => 10, 'status' => 'active']);
        $this->createdGeneratorIds[] = $generator->id;

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $this->createdUserIds[] = $subscriberUser->id;
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $this->createdSubscriberIds[] = $subscriber->id;
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id, 'status' => 'active']);
        $this->createdMeterIds[] = $meter->id;

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'requested_capacity_kw' => 5,
            'status' => 'pending',
        ]);
        $this->createdSubscriptionIds[] = $subscription->id;

        // Open a second, genuinely independent connection to the same
        // physical test database and manually hold a FOR UPDATE lock on the
        // exact generator row — standing in for "another approval request
        // already in progress for this generator, mid-transaction".
        config(['database.connections.mysql_lock_test' => config('database.connections.mysql')]);
        $second = DB::connection('mysql_lock_test');
        $second->beginTransaction();

        try {
            $second->select('select * from generators where id = ? for update', [$generator->id]);

            // A short lock-wait-timeout on the default connection so this
            // test proves the row is genuinely locked (a blocking wait that
            // fails fast) instead of hanging for MySQL's full default
            // (typically 50s) lock-wait timeout.
            DB::statement('SET SESSION innodb_lock_wait_timeout = 2');

            $threw = false;
            try {
                app(SubscriptionService::class)->updateStatus($subscription->fresh(), 'active');
            } catch (QueryException $e) {
                $threw = true;
                $this->assertStringContainsString(
                    'Lock wait timeout',
                    $e->getMessage(),
                    'Expected a lock-wait-timeout specifically, confirming the second connection really is blocked on the same row — not failing for an unrelated reason.'
                );
            }

            $this->assertTrue(
                $threw,
                'Approving a subscription must block on (and here, time out waiting for) the generator row lock while another transaction holds it — proving Generator::lockForUpdate() genuinely serializes concurrent approvals across real, separate connections.'
            );

            // The subscription must be untouched — no partial activation.
            $this->assertSame('pending', $subscription->fresh()->status->value);
        } finally {
            $second->rollBack();
        }

        // With the second connection's lock released, the exact same
        // approval now succeeds normally — proving the earlier failure was
        // genuinely about the lock, not a general breakage.
        app(SubscriptionService::class)->updateStatus($subscription->fresh(), 'active');
        $this->assertSame('active', $subscription->fresh()->status->value);
    }
}
