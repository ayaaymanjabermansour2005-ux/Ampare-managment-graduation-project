<?php

namespace Tests\Feature\MeterReading;

use App\Enums\Role as RoleEnum;
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
use Illuminate\Support\Str;
use Tests\TestCase;

class MeterReadingTest extends TestCase
{
    use RefreshDatabase;

    protected User $billingOwner;

    protected Subscription $billingSubscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);

        // سيناريو BillingCycleReadingTest الثابت.
        $this->billingOwner = User::factory()->create();
        $this->billingOwner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $generator = Generator::factory()->create(['owner_id' => $this->billingOwner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);

        $neighborhood = Neighborhood::factory()->create();

        $subscriber = Subscriber::create([
            'user_id' => $subscriberUser->id,
            'neighborhood_id' => $neighborhood->id,
            'address' => 'test address',
            'joined_at' => now(),
        ]);

        $meter = SubscriberMeter::create([
            'subscriber_id' => $subscriber->id,
            'meter_number' => 'BC-TEST-1',
            'status' => 'active',
        ]);

        $this->billingSubscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'agreed_price_per_kw' => 1,
            'currency' => 'ILS',
            'requested_capacity_kw' => 10,
            'schedule' => 'day',
            'billing_cycle' => 'weekly',
            'start_date' => '2026-07-01',
            'status' => 'active',
        ]);
    }

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    /**
     * @return array{0: User, 1: Subscription}
     */
    private function makeActiveSubscription(User $owner, array $overrides = []): array
    {
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $subscription = Subscription::factory()->create(array_merge([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'start_date' => now()->subMonth()->toDateString(),
        ], $overrides));

        return [$subscriberUser, $subscription];
    }

    private function idHeader(): array
    {
        return ['Idempotency-Key' => Str::uuid()->toString()];
    }

    public function test_subscriber_cannot_create_meter_reading(): void
    {
        $owner = $this->makeOwner();
        [$subscriberUser, $subscription] = $this->makeActiveSubscription($owner);

        $this->actingAs($subscriberUser)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/meter-readings', [
                'subscription_id' => $subscription->id,
                'reading_date' => now()->toDateString(),
                'current_reading' => 100,
            ])
            ->assertStatus(403);
    }

    public function test_owner_cannot_create_reading_for_unrelated_subscription(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($otherOwner);

        $this->actingAs($owner)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/meter-readings', [
                'subscription_id' => $subscription->id,
                'reading_date' => now()->toDateString(),
                'current_reading' => 100,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscription_id');
    }

    public function test_admin_can_create_reading_for_any_subscription(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);

        $this->actingAs($admin)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/meter-readings', [
                'subscription_id' => $subscription->id,
                'reading_date' => now()->toDateString(),
                'current_reading' => 50,
            ])
            ->assertStatus(201);
    }

    public function test_cannot_record_reading_for_inactive_subscription(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner, ['status' => 'suspended']);

        $this->actingAs($owner)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/meter-readings', [
                'subscription_id' => $subscription->id,
                'reading_date' => now()->toDateString(),
                'current_reading' => 50,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscription_id');
    }

    public function test_current_reading_cannot_be_less_than_previous(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);

        MeterReading::create([
            'subscription_id' => $subscription->id,
            'reading_date' => now()->subDays(10)->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 500,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/meter-readings', [
                'subscription_id' => $subscription->id,
                'reading_date' => now()->toDateString(),
                'current_reading' => 300,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_reading');
    }

    public function test_reading_date_cannot_be_in_future(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);

        $this->actingAs($owner)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/meter-readings', [
                'subscription_id' => $subscription->id,
                'reading_date' => now()->addDay()->toDateString(),
                'current_reading' => 100,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reading_date');
    }

    public function test_same_idempotency_key_does_not_create_duplicate_reading(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);

        $key = Str::uuid()->toString();
        $payload = [
            'subscription_id' => $subscription->id,
            'reading_date' => now()->toDateString(),
            'current_reading' => 100,
        ];

        $first = $this->actingAs($owner)
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/api/v1/meter-readings', $payload);
        $first->assertStatus(201);

        $second = $this->actingAs($owner)
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/api/v1/meter-readings', $payload);

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, MeterReading::where('subscription_id', $subscription->id)->count());
    }

    public function test_owner_can_view_reading_for_own_generator(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);
        $reading = MeterReading::create([
            'subscription_id' => $subscription->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/meter-readings/{$reading->id}")
            ->assertOk();
    }

    public function test_owner_cannot_view_reading_for_unrelated_generator(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($otherOwner);
        $reading = MeterReading::create([
            'subscription_id' => $subscription->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'created_by' => $otherOwner->id,
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/meter-readings/{$reading->id}")
            ->assertStatus(403);
    }

    public function test_subscriber_can_view_own_reading(): void
    {
        $owner = $this->makeOwner();
        [$subscriberUser, $subscription] = $this->makeActiveSubscription($owner);
        $reading = MeterReading::create([
            'subscription_id' => $subscription->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/meter-readings/{$reading->id}")
            ->assertOk();
    }

    public function test_subscriber_cannot_view_unrelated_reading(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);
        $reading = MeterReading::create([
            'subscription_id' => $subscription->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'created_by' => $owner->id,
        ]);

        [$otherSubscriberUser] = $this->makeActiveSubscription($this->makeOwner());

        $this->actingAs($otherSubscriberUser)
            ->getJson("/api/v1/meter-readings/{$reading->id}")
            ->assertStatus(403);
    }

    public function test_index_scoped_to_owner_generators(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);
        $ownReading = MeterReading::create([
            'subscription_id' => $subscription->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'created_by' => $owner->id,
        ]);

        $otherOwner = $this->makeOwner();
        [, $otherSubscription] = $this->makeActiveSubscription($otherOwner);
        MeterReading::create([
            'subscription_id' => $otherSubscription->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'created_by' => $otherOwner->id,
        ]);

        $response = $this->actingAs($owner)
            ->getJson('/api/v1/meter-readings');

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($ownReading->id));
        $this->assertCount(1, $ids);
    }

    public function test_unauthenticated_user_cannot_access_meter_readings(): void
    {
        $this->getJson('/api/v1/meter-readings')->assertStatus(401);
    }

    public function test_reading_on_due_date_creates_invoice_without_warning(): void
    {
        $response = $this->actingAs($this->billingOwner)->postJson(
            '/api/v1/meter-readings',
            [
                'subscription_id' => $this->billingSubscription->id,
                'reading_date' => '2026-07-08',
                'current_reading' => 370,
            ],
            ['Idempotency-Key' => Str::uuid()->toString()]
        );

        $response->assertCreated();
        $this->assertNull($response->json('data.reading_warning'));
        $this->assertDatabaseHas('invoices', [
            'subscription_id' => $this->billingSubscription->id,
        ]);
    }

    public function test_early_reading_gives_warning_but_still_creates_invoice(): void
    {
        $response = $this->actingAs($this->billingOwner)->postJson(
            '/api/v1/meter-readings',
            [
                'subscription_id' => $this->billingSubscription->id,
                'reading_date' => '2026-07-03',
                'current_reading' => 100,
            ],
            ['Idempotency-Key' => Str::uuid()->toString()]
        );

        $response->assertCreated();
        $this->assertNotNull($response->json('data.reading_warning'));
        $this->assertStringContainsString('قبل موعد الفوترة', $response->json('data.reading_warning'));

        $this->assertDatabaseHas('invoices', [
            'subscription_id' => $this->billingSubscription->id,
        ]);
    }

    public function test_second_reading_due_date_is_based_on_previous_reading_not_start_date(): void
    {
        $this->actingAs($this->billingOwner)->postJson(
            '/api/v1/meter-readings',
            [
                'subscription_id' => $this->billingSubscription->id,
                'reading_date' => '2026-07-08',
                'current_reading' => 370,
            ],
            ['Idempotency-Key' => Str::uuid()->toString()]
        )->assertCreated();

        $response = $this->actingAs($this->billingOwner)->postJson(
            '/api/v1/meter-readings',
            [
                'subscription_id' => $this->billingSubscription->id,
                'reading_date' => '2026-07-15',
                'current_reading' => 760,
            ],
            ['Idempotency-Key' => Str::uuid()->toString()]
        );

        $response->assertCreated();
        $this->assertNull($response->json('data.reading_warning'));
    }

    /**
     * @param  'pending_approval'|'approved'  $status
     */
    private function makeReading(Subscription $subscription, int $createdById, string $status = 'pending_approval', array $overrides = []): MeterReading
    {
        $reading = MeterReading::create(array_merge([
            'subscription_id' => $subscription->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'created_by' => $createdById,
        ], $overrides));

        $reading->forceFill(['status' => $status])->save();

        return $reading->fresh();
    }

    /**
     * FIX (تدقيق شامل — B4): approver لم يكن يُحمَّل مسبقًا لا بالقائمة ولا
     * باستجابة approve()/reject() نفسها، فحقل "تمت الموافقة من" لم يكن يظهر
     * أبدًا رغم وجود عنصر واجهة مخصَّص له.
     */
    public function test_approving_reading_exposes_approver_name_in_response_and_list(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$subscriberUser, $subscription] = $this->makeActiveSubscription($owner);
        $reading = $this->makeReading($subscription, $subscriberUser->id);

        $approveResponse = $this->actingAs($admin)
            ->patchJson("/api/v1/meter-readings/{$reading->id}/approve");

        $approveResponse->assertOk();
        $this->assertSame($admin->name, $approveResponse->json('data.approved_by'));

        $listResponse = $this->actingAs($admin)->getJson('/api/v1/meter-readings');
        $listResponse->assertOk();
        $listed = collect($listResponse->json('data.data'))->firstWhere('id', $reading->id);
        $this->assertSame($admin->name, $listed['approved_by']);
    }

    public function test_rejecting_reading_exposes_rejecter_name_in_response(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$subscriberUser, $subscription] = $this->makeActiveSubscription($owner);
        $reading = $this->makeReading($subscription, $subscriberUser->id);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/meter-readings/{$reading->id}/reject", ['reason' => 'قراءة غير منطقية']);

        $response->assertOk();
        $this->assertSame($admin->name, $response->json('data.approved_by'));
    }

    public function test_admin_can_update_pending_reading(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$subscriberUser, $subscription] = $this->makeActiveSubscription($owner);
        $reading = $this->makeReading($subscription, $subscriberUser->id);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/meter-readings/{$reading->id}", ['current_reading' => 150]);

        $response->assertOk();
        $this->assertSame(150.0, (float) $reading->fresh()->current_reading);
    }

    public function test_admin_cannot_update_approved_reading(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription] = $this->makeActiveSubscription($this->makeOwner());
        $reading = $this->makeReading($subscription, $admin->id, 'approved');

        $this->actingAs($admin)
            ->patchJson("/api/v1/meter-readings/{$reading->id}", ['current_reading' => 150])
            ->assertStatus(403);

        $this->assertSame(100.0, (float) $reading->fresh()->current_reading);
    }

    public function test_update_current_reading_cannot_be_less_than_previous(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription] = $this->makeActiveSubscription($this->makeOwner());
        $reading = $this->makeReading($subscription, $admin->id, overrides: ['previous_reading' => 100, 'current_reading' => 150]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/meter-readings/{$reading->id}", ['current_reading' => 50])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_reading');
    }

    public function test_owner_cannot_update_reading(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);
        $reading = $this->makeReading($subscription, $owner->id);

        $this->actingAs($owner)
            ->patchJson("/api/v1/meter-readings/{$reading->id}", ['current_reading' => 150])
            ->assertStatus(403);
    }

    public function test_admin_can_delete_pending_reading(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription] = $this->makeActiveSubscription($this->makeOwner());
        $reading = $this->makeReading($subscription, $admin->id);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/meter-readings/{$reading->id}")
            ->assertOk();

        $this->assertDatabaseMissing('meter_readings', ['id' => $reading->id]);
    }

    public function test_admin_cannot_delete_approved_reading(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription] = $this->makeActiveSubscription($this->makeOwner());
        $reading = $this->makeReading($subscription, $admin->id, 'approved');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/meter-readings/{$reading->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('meter_readings', ['id' => $reading->id]);
    }

    public function test_owner_cannot_delete_reading(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);
        $reading = $this->makeReading($subscription, $owner->id);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/meter-readings/{$reading->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('meter_readings', ['id' => $reading->id]);
    }
}
