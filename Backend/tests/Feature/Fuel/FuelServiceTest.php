<?php

namespace Tests\Feature\Fuel;

use App\Enums\Role;
use App\Models\FuelPurchase;
use App\Models\FuelReading;
use App\Models\Generator;
use App\Models\MeterReading;
use App\Models\Neighborhood;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Generator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole(Role::GENERATOR_OWNER->value);

        $this->generator = Generator::factory()->create([
            'owner_id' => $this->owner->id,
            'tank_capacity_liters' => 1000,
        ]);
    }

    private function createReading(float $level, string $date): void
    {
        FuelReading::create([
            'generator_id' => $this->generator->id,
            'recorded_by' => $this->owner->id,
            'tank_level_liters' => $level,
            'reading_date' => $date,
        ]);
    }

    private function createPurchase(float $liters, string $date, float $costIls = 0): void
    {
        FuelPurchase::create([
            'generator_id' => $this->generator->id,
            'recorded_by' => $this->owner->id,
            'liters' => $liters,
            'cost_amount' => $costIls,
            'currency' => 'ILS',
            'exchange_rate' => null,
            'cost_amount_ils' => $costIls,
            'purchased_at' => $date,
        ]);
    }

    private function createMeterReadingForGenerator(float $consumedKw, string $date): void
    {
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(Role::SUBSCRIBER->value);

        $neighborhood = Neighborhood::factory()->create();

        $subscriber = Subscriber::create([
            'user_id' => $subscriberUser->id,
            'neighborhood_id' => $neighborhood->id,
            'address' => 'test address',
            'joined_at' => now(),
        ]);

        $meter = SubscriberMeter::create([
            'subscriber_id' => $subscriber->id,
            'meter_number' => 'FUEL-TEST-'.uniqid(),
            'status' => 'active',
        ]);

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $this->generator->id,
            'agreed_price_per_kw' => 1,
            'currency' => 'ILS',
            'requested_capacity_kw' => 10,
            'schedule' => 'day',
            'billing_cycle' => 'weekly',
            'start_date' => '2026-06-01',
            'status' => 'active',
        ]);

        MeterReading::create([
            'subscription_id' => $subscription->id,
            'reading_date' => $date,
            'previous_reading' => 0,
            'current_reading' => $consumedKw,
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_consumption_calculation_with_purchase(): void
    {
        $this->createReading(200, '2026-07-01');
        $this->createPurchase(300, '2026-07-05');
        $this->createReading(150, '2026-07-10');

        $response = $this->actingAs($this->owner)->getJson(
            "/api/v1/generators/{$this->generator->id}/fuel/consumption?from=2026-07-01&to=2026-07-10"
        );

        $response->assertOk();
        $this->assertEquals(350.0, $response->json('data.consumed_liters'));
        $this->assertFalse($response->json('data.is_anomalous'));
    }

    public function test_consumption_calculation_without_purchase(): void
    {
        $this->createReading(200, '2026-07-01');
        $this->createReading(120, '2026-07-05');

        $response = $this->actingAs($this->owner)->getJson(
            "/api/v1/generators/{$this->generator->id}/fuel/consumption?from=2026-07-01&to=2026-07-05"
        );

        $response->assertOk();
        $this->assertEquals(80.0, $response->json('data.consumed_liters'));
    }

    public function test_cost_per_kwh_calculation(): void
    {
        $this->createReading(500, '2026-07-01');
        $this->createReading(200, '2026-07-08');
        $this->createPurchase(300, '2026-07-03', 1500);
        $this->createMeterReadingForGenerator(1000, '2026-07-05');

        $response = $this->actingAs($this->owner)->getJson(
            "/api/v1/generators/{$this->generator->id}/fuel/cost-per-kwh?from=2026-07-01&to=2026-07-08"
        );

        $response->assertOk();
        $this->assertEquals(1.5, $response->json('data.cost_per_kwh_ils'));
    }

    public function test_consumption_does_not_go_negative_on_bad_data_entry(): void
    {
        $this->createReading(100, '2026-07-01');
        $this->createPurchase(50, '2026-07-03');
        $this->createReading(300, '2026-07-05');

        $response = $this->actingAs($this->owner)->getJson(
            "/api/v1/generators/{$this->generator->id}/fuel/consumption?from=2026-07-01&to=2026-07-05"
        );

        $response->assertOk();
        $this->assertTrue($response->json('data.is_anomalous'));
        $this->assertEquals(0.0, $response->json('data.consumed_liters'));
        $this->assertNotNull($response->json('data.anomaly_note'));
    }

    public function test_cost_per_kwh_refuses_to_compute_on_anomalous_data(): void
    {
        $this->createReading(100, '2026-07-01');
        $this->createPurchase(50, '2026-07-03');
        $this->createReading(300, '2026-07-05');
        $this->createMeterReadingForGenerator(500, '2026-07-04');

        $response = $this->actingAs($this->owner)->getJson(
            "/api/v1/generators/{$this->generator->id}/fuel/cost-per-kwh?from=2026-07-01&to=2026-07-05"
        );

        $response->assertStatus(422);
    }

    public function test_owner_can_record_fuel_purchase(): void
    {
        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/generators/{$this->generator->id}/fuel/purchases",
            [
                'liters' => 100,
                'cost_amount' => 500,
                'currency' => 'ILS',
                'purchased_at' => '2026-07-01',
            ]
        );

        $response->assertCreated();
        $this->assertDatabaseHas('fuel_purchases', [
            'generator_id' => $this->generator->id,
            'liters' => 100,
        ]);
    }

    public function test_unauthorized_owner_cannot_record_fuel_purchase_for_others_generator(): void
    {
        $otherOwner = User::factory()->create();
        $otherOwner->assignRole(Role::GENERATOR_OWNER->value);

        $response = $this->actingAs($otherOwner)->postJson(
            "/api/v1/generators/{$this->generator->id}/fuel/purchases",
            [
                'liters' => 100,
                'cost_amount' => 500,
                'currency' => 'ILS',
                'purchased_at' => '2026-07-01',
            ]
        );

        $response->assertForbidden();
    }

    public function test_unrelated_subscriber_cannot_record_fuel_reading(): void
    {
        $subscriber = User::factory()->create();
        $subscriber->assignRole(Role::SUBSCRIBER->value);

        $response = $this->actingAs($subscriber)->postJson(
            "/api/v1/generators/{$this->generator->id}/fuel/readings",
            [
                'tank_level_liters' => 200,
                'reading_date' => '2026-07-01',
            ]
        );

        $response->assertForbidden();
    }
}
