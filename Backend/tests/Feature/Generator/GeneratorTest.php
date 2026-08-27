<?php

namespace Tests\Feature\Generator;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
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
     * @return array{0: Subscriber, 1: User}
     */
    private function makeSubscriberUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $user->id]);

        return [$subscriber, $user];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'مولد الحي الشرقي',
            'price_per_kw' => 2.5,
            'currency' => 'ILS',
            'capacity_kw' => 50,
            'operating_schedule' => '24h',
        ], $overrides);
    }

    public function test_owner_can_create_generator_and_owner_id_is_set_automatically(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/generators', $this->validPayload());

        $response->assertStatus(201);
        $this->assertSame($owner->id, $response->json('data.owner.id'));
        $this->assertDatabaseHas('generators', ['name' => 'مولد الحي الشرقي', 'owner_id' => $owner->id]);
    }

    public function test_admin_can_create_generator_for_specific_owner(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/generators', $this->validPayload(['owner_id' => $owner->id]));

        $response->assertStatus(201);
        $this->assertSame($owner->id, $response->json('data.owner.id'));
    }

    public function test_admin_creating_generator_requires_owner_id(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/generators', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('owner_id');
    }

    public function test_admin_cannot_assign_generator_to_non_owner_user(): void
    {
        $admin = $this->makeAdmin();
        [, $subscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($admin)
            ->postJson('/api/v1/generators', $this->validPayload(['owner_id' => $subscriberUser->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('owner_id');
    }

    public function test_subscriber_cannot_create_generator(): void
    {
        [, $subscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/generators', $this->validPayload())
            ->assertStatus(403);
    }

    public function test_custom_schedule_requires_start_and_end_time(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/generators', $this->validPayload(['operating_schedule' => 'custom']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['operating_start_time', 'operating_end_time']);
    }

    public function test_custom_schedule_end_time_must_be_after_start_time(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/generators', $this->validPayload([
                'operating_schedule' => 'custom',
                'operating_start_time' => '18:00',
                'operating_end_time' => '17:00',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('operating_end_time');
    }

    public function test_owner_can_view_own_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->getJson("/api/v1/generators/{$generator->id}")
            ->assertOk();
    }

    public function test_owner_cannot_view_another_owners_generator(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $otherOwner->id]);

        $this->actingAs($owner)
            ->getJson("/api/v1/generators/{$generator->id}")
            ->assertStatus(403);
    }

    public function test_subscriber_with_active_subscription_can_view_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
        ]);

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/generators/{$generator->id}")
            ->assertOk();
    }

    public function test_subscriber_without_subscription_cannot_view_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/generators/{$generator->id}")
            ->assertStatus(403);
    }

    public function test_linked_technician_can_view_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);
        $technician->generators()->attach($generator->id);

        $this->actingAs($technicianUser)
            ->getJson("/api/v1/generators/{$generator->id}")
            ->assertOk();
    }

    public function test_unlinked_technician_cannot_view_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);
        // بدون attach

        $this->actingAs($technicianUser)
            ->getJson("/api/v1/generators/{$generator->id}")
            ->assertStatus(403);
    }

    public function test_owner_can_update_own_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}", ['name' => 'اسم محدّث'])
            ->assertOk();

        $this->assertSame('اسم محدّث', $generator->fresh()->name);
    }

    public function test_owner_cannot_update_another_owners_generator(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $otherOwner->id]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}", ['name' => 'محاولة تعديل'])
            ->assertStatus(403);
    }

    public function test_admin_can_update_any_generator(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/generators/{$generator->id}", ['name' => 'تعديل إداري'])
            ->assertOk();
    }

    public function test_owner_can_delete_generator_without_active_subscriptions(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/generators/{$generator->id}")
            ->assertOk();

        $this->assertSoftDeleted('generators', ['id' => $generator->id]);
    }

    public function test_cannot_delete_generator_with_active_subscriptions(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [$subscriber] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/generators/{$generator->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('generator');

        $this->assertDatabaseHas('generators', ['id' => $generator->id, 'deleted_at' => null]);
    }

    public function test_owner_cannot_delete_another_owners_generator(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $otherOwner->id]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/generators/{$generator->id}")
            ->assertStatus(403);
    }

    public function test_available_returns_paginated_list_without_filters(): void
    {
        $owner = $this->makeOwner();
        Generator::factory()->count(3)->create(['owner_id' => $owner->id, 'status' => 'active']);
        [, $subscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->getJson('/api/v1/generators/available')
            ->assertOk();
    }

    public function test_available_excludes_generators_without_enough_capacity(): void
    {
        $owner = $this->makeOwner();
        $fullGenerator = Generator::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'active',
            'operating_schedule' => '24h',
            'capacity_kw' => 10,
        ]);
        $roomyGenerator = Generator::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'active',
            'operating_schedule' => '24h',
            'capacity_kw' => 100,
        ]);

        [$requesterSubscriber, $requesterUser] = $this->makeSubscriberUser();
        $requesterMeter = SubscriberMeter::factory()->create([
            'subscriber_id' => $requesterSubscriber->id,
            'status' => 'active',
        ]);

        [$otherSubscriber] = $this->makeSubscriberUser();
        $otherMeter = SubscriberMeter::factory()->create(['subscriber_id' => $otherSubscriber->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $otherMeter->id,
            'generator_id' => $fullGenerator->id,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'requested_capacity_kw' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($requesterUser)
            ->getJson('/api/v1/generators/available?' . http_build_query([
                'subscriber_meter_id' => $requesterMeter->id,
                'schedule' => 'day',
                'requested_capacity_kw' => 5,
            ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($roomyGenerator->id));
        $this->assertFalse($ids->contains($fullGenerator->id));
    }

    public function test_available_returns_empty_for_meter_not_belonging_to_requester(): void
    {
        $owner = $this->makeOwner();
        Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        [$otherSubscriber] = $this->makeSubscriberUser();
        $foreignMeter = SubscriberMeter::factory()->create(['subscriber_id' => $otherSubscriber->id]);

        [, $requesterUser] = $this->makeSubscriberUser();

        $response = $this->actingAs($requesterUser)
            ->getJson('/api/v1/generators/available?' . http_build_query([
                'subscriber_meter_id' => $foreignMeter->id,
                'schedule' => 'day',
                'requested_capacity_kw' => 5,
            ]));

        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }

    public function test_unauthenticated_user_cannot_access_generators(): void
    {
        $this->getJson('/api/v1/generators')->assertStatus(401);
    }

    public function test_owner_can_view_own_generator_timeline(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/generators/{$generator->id}/timeline")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    public function test_unrelated_owner_cannot_view_other_generator_timeline(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create();

        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/generators/{$generator->id}/timeline")
            ->assertStatus(403);
    }

    public function test_guest_cannot_view_generator_timeline(): void
    {
        $generator = Generator::factory()->create();

        $this->getJson("/api/v1/generators/{$generator->id}/timeline")
            ->assertStatus(401);
    }
}
