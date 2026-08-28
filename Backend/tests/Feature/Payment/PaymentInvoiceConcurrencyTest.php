<?php

namespace Tests\Feature\Payment;

use App\Actions\Payment\ApprovePaymentAction;
use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentReview;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * BUG-002 follow-up: PaymentTest.php's partial/sequential-approval tests
 * prove the *business logic* (recalculateStatus() now correctly locks the
 * invoice and sums payments) — but those run under RefreshDatabase, which
 * wraps every test in an uncommitted outer transaction, making it impossible
 * to prove the actual *concurrency* guarantee (that a second, genuinely
 * independent database connection is really blocked by the lock). See
 * SubscriptionCapacityConcurrencyTest.php (BUG-001) for the identical
 * constraint and the same solution applied here: real, committed data, no
 * RefreshDatabase, manual cleanup.
 */
class PaymentInvoiceConcurrencyTest extends TestCase
{
    private array $cleanup = [];

    private bool $seededRolesInSetUp = false;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--force' => true]);

        if (Role::query()->count() === 0) {
            $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
            $this->seededRolesInSetUp = true;
        }
    }

    protected function tearDown(): void
    {
        PaymentReview::whereIn('payment_id', $this->cleanup['payment'] ?? [])->forceDelete();
        Payment::whereIn('id', $this->cleanup['payment'] ?? [])->forceDelete();
        Invoice::whereIn('id', $this->cleanup['invoice'] ?? [])->forceDelete();
        Subscription::whereIn('id', $this->cleanup['subscription'] ?? [])->forceDelete();
        SubscriberMeter::whereIn('id', $this->cleanup['meter'] ?? [])->forceDelete();
        Subscriber::whereIn('id', $this->cleanup['subscriber'] ?? [])->forceDelete();
        PaymentMethod::whereIn('id', $this->cleanup['payment_method'] ?? [])->forceDelete();
        Generator::whereIn('id', $this->cleanup['generator'] ?? [])->forceDelete();
        User::whereIn('id', $this->cleanup['user'] ?? [])->forceDelete();

        // See SubscriptionCapacityConcurrencyTest.php for why: if this test
        // ran RoleSeeder (only when Role::count() === 0 at setUp time), it
        // also permanently committed a real admin@ampare.test account via
        // RoleSeeder::seedLocalAdmin() — untracked by $cleanup — which
        // firstOrCreate()-based reseeding elsewhere would never touch again,
        // silently corrupting later RefreshDatabase tests (e.g. RoleSeederTest)
        // that assume a clean admin state.
        if ($this->seededRolesInSetUp) {
            User::where('email', config('seeding.dev_admin_email', 'admin@ampare.test'))->forceDelete();
        }

        parent::tearDown();
    }

    public function test_a_second_real_connection_is_blocked_from_the_invoice_row_while_an_approval_transaction_holds_its_lock(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'currency' => 'ILS']);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $subscription = Subscription::factory()->create([
            'generator_id' => $generator->id,
            'subscriber_meter_id' => $meter->id,
            'currency' => 'ILS',
        ]);
        $invoice = Invoice::factory()->create([
            'subscription_id' => $subscription->id,
            'currency' => 'ILS',
            'amount' => 100,
            'discount_amount' => 0,
            'final_amount' => 100,
            'exchange_rate' => null,
            'final_amount_ils' => 100,
            'status' => 'pending',
        ]);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 60,
            'amount_ils' => 60,
            'status' => 'pending',
        ]);

        $this->cleanup = [
            'user' => [$owner->id, $subscriberUser->id],
            'generator' => [$generator->id],
            'subscriber' => [$subscriber->id],
            'meter' => [$meter->id],
            'subscription' => [$subscription->id],
            'invoice' => [$invoice->id],
            'payment_method' => [$method->id],
            'payment' => [$payment->id],
        ];

        // Open a second, genuinely independent connection and manually hold
        // a FOR UPDATE lock on the exact invoice row — standing in for
        // "another payment approval already in progress for this invoice".
        config(['database.connections.mysql_lock_test' => config('database.connections.mysql')]);
        $second = DB::connection('mysql_lock_test');
        $second->beginTransaction();

        try {
            $second->select('select * from invoices where id = ? for update', [$invoice->id]);

            DB::statement('SET SESSION innodb_lock_wait_timeout = 2');

            $threw = false;
            try {
                app(ApprovePaymentAction::class)->execute($payment->fresh(), $owner);
            } catch (QueryException $e) {
                $threw = true;
                $this->assertStringContainsString(
                    'Lock wait timeout',
                    $e->getMessage(),
                    'Expected a lock-wait-timeout specifically, confirming the second connection really is blocked on the invoice row.'
                );
            }

            $this->assertTrue(
                $threw,
                'Approving a payment must block on (and here, time out waiting for) the invoice row lock while another transaction holds it — proving InvoiceService::recalculateStatus() genuinely serializes concurrent approvals on the same invoice across real, separate connections.'
            );

            // No partial update: neither the payment nor the invoice changed.
            $this->assertSame('pending', $payment->fresh()->status->value);
            $this->assertSame('pending', $invoice->fresh()->status->value);
        } finally {
            $second->rollBack();
        }

        // With the second connection's lock released, the identical approval
        // now succeeds normally — proving the earlier failure was genuinely
        // about the lock, not a general breakage.
        app(ApprovePaymentAction::class)->execute($payment->fresh(), $owner);
        $this->assertSame('paid', $payment->fresh()->status->value);
        $this->assertSame('partially_paid', $invoice->fresh()->status->value);
    }
}
