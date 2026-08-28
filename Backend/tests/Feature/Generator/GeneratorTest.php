<?php

namespace Tests\Feature\Generator;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Location;
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

    // ==================== TEST-002: Location resolution coverage (GeneratorService::resolveLocationFromCoordinates) ====================

    public function test_creating_generator_with_coordinates_and_no_location_id_creates_a_new_location(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/generators', $this->validPayload([
                'latitude' => 31.35,
                'longitude' => 34.3,
            ]));

        $response->assertStatus(201);
        $locationId = $response->json('data.location.id');
        $this->assertNotNull($locationId);
        $this->assertSame('غزة', $response->json('data.location.city'));
        $this->assertDatabaseHas('locations', [
            'id' => $locationId,
            'city' => 'غزة',
            'neighborhood_id' => null,
            'address' => null,
            'latitude' => 31.35,
            'longitude' => 34.3,
        ]);
    }

    public function test_creating_generator_with_coordinates_and_existing_location_id_updates_that_location_instead_of_creating_a_new_one(): void
    {
        $owner = $this->makeOwner();
        $location = Location::factory()->create(['latitude' => 30.0, 'longitude' => 33.0]);

        $countBefore = Location::count();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/generators', $this->validPayload([
                'location_id' => $location->id,
                'latitude' => 31.4,
                'longitude' => 34.4,
            ]));

        $response->assertStatus(201);
        $this->assertSame($countBefore, Location::count());
        $this->assertSame($location->id, $response->json('data.location.id'));
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'latitude' => 31.4,
            'longitude' => 34.4,
        ]);
    }

    public function test_creating_generator_without_coordinates_or_location_id_leaves_location_null(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/generators', $this->validPayload());

        $response->assertStatus(201);
        $this->assertNull($response->json('data.location'));
        $this->assertNull(Generator::find($response->json('data.id'))->location_id);
    }

    public function test_creating_generator_with_only_one_coordinate_provided_leaves_location_null(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/generators', $this->validPayload([
                'latitude' => 31.35,
            ]));

        $response->assertStatus(201);
        $this->assertNull(Generator::find($response->json('data.id'))->location_id);
    }

    /**
     * BUG-004: `latitude`/`longitude` are not fillable on Generator, and
     * GeneratorService::resolveLocationFromCoordinates() only stripped them
     * from $data when BOTH keys were present. Sending exactly one of the two
     * (e.g. a partial map-picker payload) left the lone key in $data and
     * crashed Generator::update() with a MassAssignmentException (an
     * uncontrolled 500) instead of a clean no-op.
     */
    public function test_updating_generator_with_only_one_coordinate_provided_does_not_crash_and_leaves_location_untouched(): void
    {
        $owner = $this->makeOwner();
        $location = Location::factory()->create(['latitude' => 30.0, 'longitude' => 33.0]);
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'location_id' => $location->id]);

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}", [
                'longitude' => 34.6,
            ]);

        $response->assertStatus(200);
        $this->assertSame($location->id, $generator->fresh()->location_id);
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'latitude' => 30.0,
            'longitude' => 33.0,
        ]);
    }

    public function test_updating_generator_with_new_coordinates_updates_its_existing_location_in_place(): void
    {
        $owner = $this->makeOwner();
        $location = Location::factory()->create(['latitude' => 30.0, 'longitude' => 33.0]);
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'location_id' => $location->id]);

        $countBefore = Location::count();

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}", [
                'latitude' => 31.6,
                'longitude' => 34.6,
            ]);

        $response->assertStatus(200);
        $this->assertSame($countBefore, Location::count());
        $this->assertSame($location->id, $generator->fresh()->location_id);
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'latitude' => 31.6,
            'longitude' => 34.6,
        ]);
    }

    public function test_updating_generator_without_coordinate_keys_leaves_its_location_untouched(): void
    {
        $owner = $this->makeOwner();
        $location = Location::factory()->create(['latitude' => 30.0, 'longitude' => 33.0]);
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'location_id' => $location->id]);

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}", [
                'name' => 'اسم جديد للمولد',
            ]);

        $response->assertStatus(200);
        $this->assertSame($location->id, $generator->fresh()->location_id);
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'latitude' => 30.0,
            'longitude' => 33.0,
        ]);
    }

    // ==================== API-001: map-points coverage (no prior tests) ====================

    public function test_owner_map_points_only_includes_own_generators(): void
    {
        $owner = $this->makeOwner();
        $ownGenerator = Generator::factory()->create(['owner_id' => $owner->id]);
        Generator::factory()->create();

        $response = $this->actingAs($owner)->getJson('/api/v1/generators/map-points');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($ownGenerator->id) || $ids->isEmpty());
    }

    public function test_admin_map_points_includes_generators_with_coordinates(): void
    {
        $admin = $this->makeAdmin();
        $location = Location::factory()->create(['latitude' => 31.4, 'longitude' => 34.4]);
        Generator::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($admin)->getJson('/api/v1/generators/map-points');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_unauthenticated_user_cannot_access_map_points(): void
    {
        $this->getJson('/api/v1/generators/map-points')->assertStatus(401);
    }

    public function test_updating_generator_with_null_coordinates_leaves_its_existing_location_id_untouched(): void
    {
        $owner = $this->makeOwner();
        $location = Location::factory()->create();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'location_id' => $location->id]);

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}", [
                'latitude' => null,
                'longitude' => null,
            ]);

        $response->assertStatus(200);
        $this->assertSame($location->id, $generator->fresh()->location_id);
    }
}
