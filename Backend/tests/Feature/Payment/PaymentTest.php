<?php

namespace Tests\Feature\Payment;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeScenario(string $currency = 'ILS', float $amount = 100.0): array
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);

        $generator = Generator::factory()->create([
            'owner_id' => $owner->id,
            'currency' => $currency,
        ]);

        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $subscription = Subscription::factory()->create([
            'generator_id' => $generator->id,
            'subscriber_meter_id' => $meter->id,
            'currency' => $currency,
        ]);

        $exchangeRate = $currency === 'USD' ? 3.70 : null;
        $amountIls = $currency === 'USD' ? round($amount * 3.70, 2) : $amount;

        $invoice = Invoice::factory()->create([
            'subscription_id' => $subscription->id,
            'currency' => $currency,
            'amount' => $amount,
            'discount_amount' => 0,
            'final_amount' => $amount,
            'exchange_rate' => $exchangeRate,
            'final_amount_ils' => $amountIls,
            'status' => 'pending',
        ]);

        return compact('owner', 'subscriberUser', 'generator', 'subscription', 'invoice');
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }

    private function fakeExchangeRate(float $rate = 3.70): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response(['rates' => ['ILS' => $rate]], 200),
        ]);
    }

    private function postPayment(User $actingAs, array $payload)
    {
        return $this->actingAs($actingAs)
            ->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', $payload);
    }

    public function test_currency_is_derived_automatically_from_bank_payment_method(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);

        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);

        $response = $this->postPayment($subscriber, [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 50,
            'attachments' => [UploadedFile::fake()->image('proof.jpg')],
        ]);

        $response->assertStatus(201);
        $this->assertSame('ILS', $response->json('data.currency'));
        $this->assertEquals(50.0, $response->json('data.amount_ils'));
    }

    public function test_currency_input_is_prohibited_when_payment_method_has_fixed_currency(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);

        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);

        $response = $this->postPayment($subscriber, [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 50,
            'currency' => 'USD',
            'attachments' => [UploadedFile::fake()->image('proof.jpg')],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('currency');
    }

    public function test_cash_payment_requires_explicit_currency(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);

        $method = PaymentMethod::factory()->cash()->create(['user_id' => $owner->id]);

        $response = $this->postPayment($subscriber, [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 30,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('currency');
    }

    public function test_cash_payment_with_explicit_currency_succeeds(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);

        $method = PaymentMethod::factory()->cash()->create(['user_id' => $owner->id]);

        $response = $this->postPayment($subscriber, [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 30,
            'currency' => 'ILS',
        ]);

        $response->assertStatus(201);
        $this->assertSame('ILS', $response->json('data.currency'));
    }

    public function test_usd_payment_converts_to_ils_using_live_exchange_rate(): void
    {
        $this->fakeExchangeRate(3.70);

        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('USD', 100);

        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'USD']);

        $response = $this->postPayment($subscriber, [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 20,
            'attachments' => [UploadedFile::fake()->image('proof.jpg')],
        ]);

        $response->assertStatus(201);
        $this->assertSame('USD', $response->json('data.currency'));
        $this->assertSame(3.7, $response->json('data.exchange_rate'));
        $this->assertEquals(74.0, $response->json('data.amount_ils'));
    }

    public function test_usd_payment_rejected_when_exchange_rate_api_is_down(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response([], 500),
        ]);

        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('USD', 100);

        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'USD']);

        $response = $this->postPayment($subscriber, [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 20,
            'attachments' => [UploadedFile::fake()->image('proof.jpg')],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('amount');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_amount_exceeding_remaining_ils_balance_is_rejected(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);

        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);

        $response = $this->postPayment($subscriber, [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 150,
            'attachments' => [UploadedFile::fake()->image('proof.jpg')],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('amount');
    }

    // ==================== TEST-001: idempotency replay (EnsureIdempotency) ====================
    // The original audit's own finding: the idempotency middleware + DB
    // primary-key enforcement is specifically built to prevent double-
    // charging on client retry, but no test previously proved it actually
    // dedupes — postPayment() (above) sends a *fresh* UUID key on every
    // call, never the same key twice, so the replay path was never
    // exercised by any existing test.

    public function test_repeating_the_same_idempotency_key_and_payload_does_not_create_a_second_payment(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $key = Str::uuid()->toString();
        $payload = [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 50,
            'attachments' => [UploadedFile::fake()->image('proof.jpg')],
        ];

        $first = $this->actingAs($subscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', $payload);
        $first->assertStatus(201);
        $this->assertSame(1, Payment::count());

        $second = $this->actingAs($subscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', $payload);

        // The stored prior response is replayed verbatim — same status,
        // same data. assertEquals (not assertSame): MySQL's native JSON
        // column type canonicalizes/re-orders object keys on storage, so
        // the replayed response's array key order legitimately differs
        // from the fresh one even though every value is identical —
        // confirmed by direct inspection before choosing this assertion,
        // not assumed. Order-independent equality is what actually matters
        // here, not incidental key ordering.
        $second->assertStatus(201);
        $this->assertEquals($first->json(), $second->json());

        // The real proof: the underlying operation did NOT run twice.
        $this->assertSame(1, Payment::count());
    }

    public function test_reusing_an_idempotency_key_from_a_different_user_is_rejected(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $key = Str::uuid()->toString();

        $this->actingAs($subscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'payment_method_id' => $method->id,
                'amount' => 50,
                'attachments' => [UploadedFile::fake()->image('proof.jpg')],
            ])->assertStatus(201);

        $otherSubscriber = User::factory()->create();
        $otherSubscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        $this->actingAs($otherSubscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'payment_method_id' => $method->id,
                'amount' => 50,
                'attachments' => [UploadedFile::fake()->image('proof.jpg')],
            ])->assertStatus(409);

        $this->assertSame(1, Payment::count());
    }

    public function test_reusing_an_idempotency_key_on_a_different_route_is_rejected(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $key = Str::uuid()->toString();

        $this->actingAs($subscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'payment_method_id' => $method->id,
                'amount' => 50,
                'attachments' => [UploadedFile::fake()->image('proof.jpg')],
            ])->assertStatus(201);

        // Same key, genuinely different idempotency-gated route
        // ('payments/gateway', not 'payments') — the EnsureIdempotency
        // middleware runs before route-model-binding/FormRequest
        // validation, so the route-mismatch check fires first regardless
        // of this second request's payload shape; an empty payload is
        // enough to prove the rejection is about the route, not a
        // validation failure.
        $this->actingAs($subscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments/gateway', [])
            ->assertStatus(409);

        $this->assertSame(1, Payment::count());
    }

    public function test_reusing_an_idempotency_key_with_a_genuinely_different_payload_is_rejected(): void
    {
        // IDEMPOTENCY-payload fix: EnsureIdempotency now hashes the request
        // payload (fields + file fingerprints) and stores it alongside the
        // key. Reusing the same key with a genuinely different payload on
        // the same route/user now errors with 409 instead of silently
        // replaying the first call's stored response — closing the "silent
        // wrong-answer" footgun the prior behavior left open.
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $key = Str::uuid()->toString();

        $first = $this->actingAs($subscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'payment_method_id' => $method->id,
                'amount' => 50,
                'attachments' => [UploadedFile::fake()->image('proof.jpg')],
            ]);
        $first->assertStatus(201);

        $second = $this->actingAs($subscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'payment_method_id' => $method->id,
                'amount' => 30, // genuinely different amount — now rejected, not silently replayed.
                'attachments' => [UploadedFile::fake()->image('proof2.jpg')],
            ]);

        $second->assertStatus(409);
        $this->assertSame(1, Payment::count());
        $this->assertSame(50.0, (float) Payment::first()->amount);
    }

    public function test_reusing_an_idempotency_key_with_an_identical_payload_still_replays_correctly(): void
    {
        // Sibling of the mismatch test above: the new payload-hash check
        // must not create false-positive 409s for a genuine retry with the
        // exact same data (the actual, common case idempotency exists for).
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $key = Str::uuid()->toString();
        $payload = [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 50,
        ];

        $first = $this->actingAs($subscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', $payload);
        $first->assertStatus(201);

        $second = $this->actingAs($subscriber)
            ->withHeader('Idempotency-Key', $key)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/payments', $payload);

        // assertEquals, not assertSame — see the sibling replay test above
        // for why (MySQL JSON column key-order canonicalization).
        $this->assertEquals($first->json(), $second->json());
        $this->assertSame(1, Payment::count());
    }

    public function test_owner_can_approve_pending_payment(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 100,
            'currency' => 'ILS',
            'amount_ils' => 100,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patchJson("/api/v1/payments/{$payment->id}/approve");

        $response->assertOk();
        $this->assertSame('paid', $payment->fresh()->status->value);
        $this->assertSame('paid', $invoice->fresh()->status->value);
    }

    public function test_subscriber_cannot_approve_own_payment(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'status' => 'pending',
        ]);

        $this->actingAs($subscriber)
            ->patchJson("/api/v1/payments/{$payment->id}/approve")
            ->assertStatus(403);

        $this->assertSame('pending', $payment->fresh()->status->value);
    }

    public function test_owner_cannot_approve_other_owners_payment(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'status' => 'pending',
        ]);

        $unrelatedOwner = User::factory()->create();
        $unrelatedOwner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($unrelatedOwner)
            ->patchJson("/api/v1/payments/{$payment->id}/approve")
            ->assertStatus(403);
    }

    public function test_admin_can_approve_any_payment(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 100,
            'amount_ils' => 100,
            'status' => 'pending',
        ]);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patchJson("/api/v1/payments/{$payment->id}/approve")
            ->assertOk();

        $this->assertSame('paid', $payment->fresh()->status->value);
    }

    // ==================== تدقيق شامل: ربط clearCache() بأحداث دورة حياة الدفعة ====================

    /**
     * الدفعة هون جزئية (40 من أصل 100) فلا تُحوّل الفاتورة لمدفوعة بالكامل،
     * أي أن InvoicePaid لن يُطلَق — الاختبار يتأكد أن PaymentApproved وحدها
     * كافية لمسح كاش الداشبورد (سيناريو كان مفقودًا فعليًا قبل الإصلاح: كانت
     * payments_pending_count وpayments_financial_summary تبقى قديمة حتى انتهاء الـTTL).
     */
    public function test_approving_a_partial_payment_clears_the_admin_dashboard_cache(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 40,
            'amount_ils' => 40,
            'status' => 'pending',
        ]);

        Cache::put('admin.dashboard.stats_v2', ['stale' => true], 300);

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->patchJson("/api/v1/payments/{$payment->id}/approve")
            ->assertOk();

        $this->assertSame('partially_paid', $invoice->fresh()->status->value);
        $this->assertNull(Cache::get('admin.dashboard.stats_v2'));
    }

    public function test_rejecting_a_payment_clears_the_admin_dashboard_cache(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'status' => 'pending',
        ]);

        Cache::put('admin.dashboard.stats_v2', ['stale' => true], 300);

        $this->actingAs($owner)
            ->patchJson("/api/v1/payments/{$payment->id}/reject", ['reason' => 'لا يطابق.'])
            ->assertOk();

        $this->assertNull(Cache::get('admin.dashboard.stats_v2'));
    }

    public function test_owner_can_reject_payment_with_reason(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patchJson("/api/v1/payments/{$payment->id}/reject", [
            'reason' => 'المبلغ لا يطابق الفاتورة.',
        ]);

        $response->assertOk();
        $this->assertSame('rejected', $payment->fresh()->status->value);
        $this->assertSame('المبلغ لا يطابق الفاتورة.', $payment->fresh()->rejection_reason);
    }

    // ==================== BUG-002: invoice status recalculation under concurrent payment approval ====================

    public function test_rejecting_a_payment_does_not_change_the_invoice_status(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 100,
            'amount_ils' => 100,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/payments/{$payment->id}/reject", ['reason' => 'لا يطابق.'])
            ->assertOk();

        $this->assertSame('pending', $invoice->fresh()->status->value);
    }

    public function test_partial_payment_approval_leaves_invoice_partially_paid(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 40,
            'amount_ils' => 40,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/payments/{$payment->id}/approve")
            ->assertOk();

        $this->assertSame('partially_paid', $invoice->fresh()->status->value);
    }

    public function test_two_sequential_partial_payment_approvals_result_in_a_fully_paid_invoice(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);

        $firstPayment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 60,
            'amount_ils' => 60,
            'status' => 'pending',
        ]);
        $secondPayment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 40,
            'amount_ils' => 40,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)->patchJson("/api/v1/payments/{$firstPayment->id}/approve")->assertOk();
        $this->assertSame('partially_paid', $invoice->fresh()->status->value);

        $this->actingAs($owner)->patchJson("/api/v1/payments/{$secondPayment->id}/approve")->assertOk();
        $this->assertSame('paid', $invoice->fresh()->status->value);

        // The invariant this fix protects: the invoice's recorded status must
        // always genuinely reflect the true sum of its Paid payments — not a
        // stale snapshot from whichever approval happened to commit last.
        $trueSum = Payment::where('invoice_id', $invoice->id)->where('status', 'paid')->sum('amount_ils');
        $this->assertSame(100.0, (float) $trueSum);
    }

    public function test_admin_adjustment_payment_also_recalculates_invoice_status_correctly(): void
    {
        // Exercises the OTHER pre-existing recalculateStatus() call site
        // (CreatePaymentAction::createAdjustmentPayment), which already
        // locked the invoice before this fix — confirming the fix's
        // internal lockForUpdate() doesn't break that already-correct path.
        ['invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $admin = $this->makeAdmin();

        $this->postPayment($admin, [
            'invoice_id' => $invoice->id,
            'amount' => 100,
        ])->assertCreated();

        $this->assertSame('paid', $invoice->fresh()->status->value);
    }

    // Note: the real cross-connection concurrency proof for this fix lives in
    // a dedicated file, PaymentInvoiceConcurrencyTest.php, NOT here — this
    // class uses RefreshDatabase (an uncommitted outer transaction per
    // test), which a genuinely separate DB connection cannot see into or
    // lock against. See that file for why, mirroring BUG-001's identical
    // constraint and solution (SubscriptionCapacityConcurrencyTest.php).

    public function test_owner_requests_correction_then_subscriber_resubmits(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 100,
            'currency' => 'ILS',
            'amount_ils' => 100,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/payments/{$payment->id}/needs-correction", [
                'note' => 'صورة الإيصال غير واضحة.',
            ])
            ->assertOk();

        $this->assertSame('needs_correction', $payment->fresh()->status->value);

        $this->actingAs($subscriber)
            ->patchJson("/api/v1/payments/{$payment->id}/resubmit", [
                'note' => 'تم رفع صورة أوضح.',
            ])
            ->assertOk();

        $this->assertSame('pending', $payment->fresh()->status->value);
    }

    public function test_subscriber_can_cancel_own_pending_payment(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'status' => 'pending',
        ]);

        $this->actingAs($subscriber)
            ->patchJson("/api/v1/payments/{$payment->id}/cancel")
            ->assertOk();

        $this->assertSame('cancelled', $payment->fresh()->status->value);
    }

    public function test_approving_payment_creates_audit_trail_entry(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 100,
            'amount_ils' => 100,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)->patchJson("/api/v1/payments/{$payment->id}/approve")->assertOk();

        $this->assertDatabaseHas('payment_reviews', [
            'payment_id' => $payment->id,
            'reviewed_by' => $owner->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_adjustment_within_balance_succeeds_without_reason(): void
    {
        ['invoice' => $invoice] = $this->makeScenario('ILS', 100.0);
        $admin = $this->makeAdmin();

        $response = $this->postPayment($admin, [
            'invoice_id' => $invoice->id,
            'amount' => 100.0,
            'note' => 'تسوية إدارية عادية.',
        ]);

        $response->assertStatus(201);

        $payment = Payment::first();
        $this->assertSame('تسوية إدارية عادية.', $payment->note);
        $this->assertNull($payment->review_note);
        $this->assertSame(0, PaymentReview::count());
    }

    public function test_admin_exceeding_balance_without_override_reason_is_rejected(): void
    {
        ['invoice' => $invoice] = $this->makeScenario('ILS', 100.0);
        $admin = $this->makeAdmin();

        $response = $this->postPayment($admin, [
            'invoice_id' => $invoice->id,
            'amount' => 150.0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('override_reason');

        $this->assertSame(0, Payment::count());
    }

    public function test_admin_exceeding_balance_with_override_reason_succeeds_and_is_audited(): void
    {
        ['invoice' => $invoice] = $this->makeScenario('ILS', 100.0);
        $admin = $this->makeAdmin();

        $response = $this->postPayment($admin, [
            'invoice_id' => $invoice->id,
            'amount' => 150.0,
            'override_reason' => 'تسوية تصحيحية لخطأ سابق في احتساب الفاتورة رقم '.$invoice->id.'.',
        ]);

        $response->assertStatus(201);

        $payment = Payment::first();
        $this->assertSame(
            'تسوية تصحيحية لخطأ سابق في احتساب الفاتورة رقم '.$invoice->id.'.',
            $payment->review_note
        );
        $this->assertSame($admin->id, $payment->reviewed_by);

        $this->assertSame(1, PaymentReview::count());
        $review = PaymentReview::first();
        $this->assertSame($payment->id, $review->payment_id);
        $this->assertSame($admin->id, $review->reviewed_by);
        $this->assertSame('approved', $review->status->value);
    }

    public function test_subscriber_cannot_exceed_balance_even_with_override_reason(): void
    {
        ['subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100.0);

        $response = $this->postPayment($subscriber, [
            'invoice_id' => $invoice->id,
            'amount' => 150.0,
            'override_reason' => 'محاولة تمرير سبب لا يخصني.',
        ]);

        $response->assertStatus(422);

        $this->assertSame(0, Payment::count());
    }

    public function test_admin_note_field_is_persisted_alongside_override(): void
    {
        ['invoice' => $invoice] = $this->makeScenario('ILS', 100.0);
        $admin = $this->makeAdmin();

        $this->postPayment($admin, [
            'invoice_id' => $invoice->id,
            'amount' => 150.0,
            'note' => 'ملاحظة عامة منفصلة عن سبب التجاوز.',
            'override_reason' => 'سبب التجاوز الفعلي.',
        ])->assertStatus(201);

        $payment = Payment::first();
        $this->assertSame('ملاحظة عامة منفصلة عن سبب التجاوز.', $payment->note);
        $this->assertSame('سبب التجاوز الفعلي.', $payment->review_note);
    }

    public function test_technician_with_manually_granted_payments_permission_sees_no_payments(): void
    {
        ['invoice' => $invoice] = $this->makeScenario('ILS', 100.0);

        $method = PaymentMethod::factory()->bank()->create(['currency' => 'ILS']);
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'status' => 'pending',
        ]);

        $technician = User::factory()->create();
        $technician->assignRole(RoleEnum::TECHNICIAN->value);
        $technician->givePermissionTo('payments.view');

        $response = $this->actingAs($technician)->getJson('/api/v1/payments');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.data'));
    }

    public function test_owner_and_subscriber_still_see_their_own_payments_after_fail_safe_fix(): void
    {
        ['owner' => $owner, 'subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100.0);

        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'status' => 'pending',
        ]);

        $this->assertCount(1, $this->actingAs($owner)->getJson('/api/v1/payments')->json('data.data'));
        $this->assertCount(1, $this->actingAs($subscriber)->getJson('/api/v1/payments')->json('data.data'));
    }

    public function test_payment_method_account_number_is_masked_in_api_response(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)->postJson('/api/v1/payment-methods', [
            'type' => 'bank',
            'currency' => 'ILS',
            'bank_name' => 'بنك فلسطين',
            'account_name' => 'أحمد محمد',
            'account_number' => '1234567890',
        ]);

        $response->assertStatus(201);
        $this->assertSame('******7890', $response->json('data.account_number_masked'));
        $this->assertArrayNotHasKey('account_number', $response->json('data'));
    }

    public function test_payment_method_account_number_is_encrypted_at_rest(): void
    {
        $owner = $this->makeOwner();

        $method = PaymentMethod::factory()->bank()->create([
            'user_id' => $owner->id,
            'account_number' => '1234567890',
        ]);

        $rawValue = DB::table('payment_methods')->where('id', $method->id)->value('account_number');

        $this->assertNotSame('1234567890', $rawValue);
        $this->assertSame('1234567890', $method->fresh()->account_number);
    }

    public function test_approve_response_exposes_processed_by_not_approved_by(): void
    {
        ['owner' => $owner, 'invoice' => $invoice] = $this->makeScenario('ILS', 100.0);
        $method = PaymentMethod::factory()->bank()->create(['user_id' => $owner->id, 'currency' => 'ILS']);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 100,
            'amount_ils' => 100,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patchJson("/api/v1/payments/{$payment->id}/approve");

        $response->assertOk();
        $this->assertSame($owner->id, $response->json('data.processed_by.id'));
        $this->assertArrayNotHasKey('approved_by', $response->json('data'));
    }

    public function test_resubmitting_payment_with_amount_exceeding_remaining_balance_is_rejected(): void
    {
        ['subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100.0);

        $payment = Payment::factory()->needsCorrection()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50,
            'currency' => 'ILS',
            'amount_ils' => 50,
        ]);

        $response = $this->actingAs($subscriber)
            ->patchJson("/api/v1/payments/{$payment->id}/resubmit", [
                'amount' => 150,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('amount');

        $this->assertSame('needs_correction', $payment->fresh()->status->value);
        $this->assertEquals(50.0, (float) $payment->fresh()->amount);
    }

    public function test_resubmitting_payment_with_valid_amount_transitions_to_pending(): void
    {
        ['subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100.0);

        $payment = Payment::factory()->needsCorrection()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50,
            'currency' => 'ILS',
            'amount_ils' => 50,
        ]);

        $response = $this->actingAs($subscriber)
            ->patchJson("/api/v1/payments/{$payment->id}/resubmit", [
                'amount' => 80,
            ]);

        $response->assertOk();
        $this->assertSame('pending', $payment->fresh()->status->value);
        $this->assertEquals(80.0, (float) $payment->fresh()->amount);
    }

    public function test_cancelling_needs_correction_payment_succeeds(): void
    {
        ['subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100.0);

        $payment = Payment::factory()->needsCorrection()->create([
            'invoice_id' => $invoice->id,
        ]);

        $response = $this->actingAs($subscriber)->patchJson("/api/v1/payments/{$payment->id}/cancel");

        $response->assertOk();
        $this->assertSame('cancelled', $payment->fresh()->status->value);
    }

    public function test_cancelling_paid_payment_is_rejected(): void
    {
        ['subscriberUser' => $subscriber, 'invoice' => $invoice] = $this->makeScenario('ILS', 100.0);

        $payment = Payment::factory()->paid()->create([
            'invoice_id' => $invoice->id,
        ]);

        $response = $this->actingAs($subscriber)->patchJson("/api/v1/payments/{$payment->id}/cancel");

        $response->assertStatus(403);
        $this->assertSame('paid', $payment->fresh()->status->value);
    }
}
