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
            'override_reason' => 'تسوية تصحيحية لخطأ سابق في احتساب الفاتورة رقم ' . $invoice->id . '.',
        ]);

        $response->assertStatus(201);

        $payment = Payment::first();
        $this->assertSame(
            'تسوية تصحيحية لخطأ سابق في احتساب الفاتورة رقم ' . $invoice->id . '.',
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
