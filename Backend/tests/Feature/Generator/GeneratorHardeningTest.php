<?php

namespace Tests\Feature\Generator;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Plan;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratorHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeOwner(?Plan $plan = null): User
    {
        $owner = User::factory()->create(['plan_id' => $plan?->id]);
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }
    public function test_cannot_deactivate_generator_with_active_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}", ['status' => 'maintenance'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('generator');

        $this->assertSame('active', $generator->fresh()->status->value);
    }

    public function test_can_deactivate_generator_without_active_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}", ['status' => 'maintenance'])
            ->assertOk();

        $this->assertSame('maintenance', $generator->fresh()->status->value);
    }

    public function test_updating_other_fields_without_changing_status_is_unaffected_by_active_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}", ['name' => 'اسم جديد'])
            ->assertOk();

        $this->assertSame('اسم جديد', $generator->fresh()->name);
    }

    public function test_cannot_create_generator_beyond_plan_limit(): void
    {
        $plan = Plan::create([
            'name' => 'خطة محدودة',
            'code' => 'lim-' . \Illuminate\Support\Str::random(6),
            'max_generators' => 1,
            'price_monthly' => 0,
            'currency' => 'ILS',
            'is_active' => true,
        ]);

        $owner = $this->makeOwner($plan);
        Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson('/api/v1/generators', [
                'name' => 'مولد ثاني',
                'price_per_kw' => 2.5,
                'currency' => 'ILS',
                'operating_schedule' => '24h',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('plan');
    }

    public function test_can_create_generator_when_under_plan_limit(): void
    {
        $plan = Plan::create([
            'name' => 'خطة متوسطة',
            'code' => 'med-' . \Illuminate\Support\Str::random(6),
            'max_generators' => 2,
            'price_monthly' => 0,
            'currency' => 'ILS',
            'is_active' => true,
        ]);

        $owner = $this->makeOwner($plan);
        Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson('/api/v1/generators', [
                'name' => 'مولد ثاني',
                'price_per_kw' => 2.5,
                'currency' => 'ILS',
                'operating_schedule' => '24h',
            ])
            ->assertStatus(201);
    }

    public function test_null_max_generators_means_unlimited(): void
    {
        $plan = Plan::create([
            'name' => 'خطة غير محدودة',
            'code' => 'unl-' . \Illuminate\Support\Str::random(6),
            'max_generators' => null,
            'price_monthly' => 0,
            'currency' => 'ILS',
            'is_active' => true,
        ]);

        $owner = $this->makeOwner($plan);
        Generator::factory()->count(5)->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson('/api/v1/generators', [
                'name' => 'مولد سادس',
                'price_per_kw' => 2.5,
                'currency' => 'ILS',
                'operating_schedule' => '24h',
            ])
            ->assertStatus(201);
    }
}
