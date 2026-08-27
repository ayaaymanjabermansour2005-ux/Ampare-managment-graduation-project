<?php

namespace Tests\Feature\Payment;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * اختبارات بوابة الدفع الوهمية (طلب المستخدم).
 */
class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    /**
     * @return array{0: User, 1: Invoice}
     */
    private function makeInvoiceForSubscriber(float $finalAmountIls = 100): array
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
        ]);

        $invoice = Invoice::factory()->create([
            'subscription_id' => $subscription->id,
            'currency' => 'ILS',
            'amount' => $finalAmountIls,
            'discount_amount' => 0,
            'final_amount' => $finalAmountIls,
            'final_amount_ils' => $finalAmountIls,
            'exchange_rate' => null,
            'status' => 'pending',
        ]);

        return [$subscriberUser, $invoice];
    }

    private function idempotencyHeader(): array
    {
        return ['Idempotency-Key' => Str::uuid()->toString()];
    }

    public function test_successful_card_pays_invoice_immediately(): void
    {
        [$subscriberUser, $invoice] = $this->makeInvoiceForSubscriber(100);

        $response = $this->actingAs($subscriberUser)
            ->withHeaders($this->idempotencyHeader())
            ->postJson('/api/v1/payments/gateway', [
                'invoice_id' => $invoice->id,
                'amount' => 100,
                'card_number' => '4242424242424242',
                'card_holder_name' => 'Test User',
                'expiry_month' => '12',
                'expiry_year' => '30',
                'cvv' => '123',
            ]);

        $response->assertStatus(201);
        $this->assertSame('paid', $invoice->fresh()->status->value);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'status' => 'paid',
            'source' => 'gateway',
        ]);
    }

    public function test_decline_test_card_is_rejected(): void
    {
        [$subscriberUser, $invoice] = $this->makeInvoiceForSubscriber(100);

        $response = $this->actingAs($subscriberUser)
            ->withHeaders($this->idempotencyHeader())
            ->postJson('/api/v1/payments/gateway', [
                'invoice_id' => $invoice->id,
                'amount' => 100,
                'card_number' => '4000000000000002',
                'card_holder_name' => 'Test User',
                'expiry_month' => '12',
                'expiry_year' => '30',
                'cvv' => '123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('card_number');

        $this->assertSame('pending', $invoice->fresh()->status->value);
        $this->assertDatabaseMissing('payments', ['invoice_id' => $invoice->id]);
    }

    public function test_invalid_luhn_card_number_is_rejected(): void
    {
        [$subscriberUser, $invoice] = $this->makeInvoiceForSubscriber(100);

        $this->actingAs($subscriberUser)
            ->withHeaders($this->idempotencyHeader())
            ->postJson('/api/v1/payments/gateway', [
                'invoice_id' => $invoice->id,
                'amount' => 100,
                'card_number' => '1234567890123456',
                'card_holder_name' => 'Test User',
                'expiry_month' => '12',
                'expiry_year' => '30',
                'cvv' => '123',
            ])
            ->assertStatus(422);
    }

    public function test_expired_card_is_rejected(): void
    {
        [$subscriberUser, $invoice] = $this->makeInvoiceForSubscriber(100);

        $this->actingAs($subscriberUser)
            ->withHeaders($this->idempotencyHeader())
            ->postJson('/api/v1/payments/gateway', [
                'invoice_id' => $invoice->id,
                'amount' => 100,
                'card_number' => '4242424242424242',
                'card_holder_name' => 'Test User',
                'expiry_month' => '01',
                'expiry_year' => '20',
                'cvv' => '123',
            ])
            ->assertStatus(422);
    }

    public function test_amount_exceeding_balance_is_rejected(): void
    {
        [$subscriberUser, $invoice] = $this->makeInvoiceForSubscriber(100);

        $this->actingAs($subscriberUser)
            ->withHeaders($this->idempotencyHeader())
            ->postJson('/api/v1/payments/gateway', [
                'invoice_id' => $invoice->id,
                'amount' => 500,
                'card_number' => '4242424242424242',
                'card_holder_name' => 'Test User',
                'expiry_month' => '12',
                'expiry_year' => '30',
                'cvv' => '123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_partial_payment_leaves_invoice_partially_paid(): void
    {
        [$subscriberUser, $invoice] = $this->makeInvoiceForSubscriber(100);

        $this->actingAs($subscriberUser)
            ->withHeaders($this->idempotencyHeader())
            ->postJson('/api/v1/payments/gateway', [
                'invoice_id' => $invoice->id,
                'amount' => 40,
                'card_number' => '4242424242424242',
                'card_holder_name' => 'Test User',
                'expiry_month' => '12',
                'expiry_year' => '30',
                'cvv' => '123',
            ])
            ->assertStatus(201);

        $this->assertSame('partially_paid', $invoice->fresh()->status->value);
    }

    public function test_unrelated_subscriber_cannot_pay_others_invoice(): void
    {
        [, $invoice] = $this->makeInvoiceForSubscriber(100);

        $stranger = User::factory()->create();
        $stranger->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $stranger->id]);

        $this->actingAs($stranger)
            ->withHeaders($this->idempotencyHeader())
            ->postJson('/api/v1/payments/gateway', [
                'invoice_id' => $invoice->id,
                'amount' => 100,
                'card_number' => '4242424242424242',
                'card_holder_name' => 'Test User',
                'expiry_month' => '12',
                'expiry_year' => '30',
                'cvv' => '123',
            ])
            ->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_use_gateway(): void
    {
        [, $invoice] = $this->makeInvoiceForSubscriber(100);

        $this->postJson('/api/v1/payments/gateway', [
            'invoice_id' => $invoice->id,
            'amount' => 100,
            'card_number' => '4242424242424242',
            'card_holder_name' => 'Test User',
            'expiry_month' => '12',
            'expiry_year' => '30',
            'cvv' => '123',
        ])->assertStatus(401);
    }
}
