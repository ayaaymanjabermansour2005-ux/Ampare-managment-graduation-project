<?php

namespace Tests\Feature\SubscriberMeter;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberMeterTest extends TestCase
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

    public function test_subscriber_can_create_meter(): void
    {
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();

        $response = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/subscriber-meters', [
                'meter_number' => 'MTR-1001',
                'property_label' => 'المنزل الرئيسي',
            ]);

        $response->assertStatus(201);
        $this->assertSame('active', $response->json('data.status'));
        $this->assertDatabaseHas('subscriber_meters', [
            'subscriber_id' => $subscriber->id,
            'meter_number' => 'MTR-1001',
            'status' => 'active',
        ]);
    }

    public function test_meter_number_must_be_unique(): void
    {
        [$subscriber] = $this->makeSubscriberUser();
        SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id, 'meter_number' => 'MTR-DUP']);

        [, $otherSubscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($otherSubscriberUser)
            ->postJson('/api/v1/subscriber-meters', ['meter_number' => 'MTR-DUP'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('meter_number');
    }

    public function test_owner_cannot_create_meter(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/subscriber-meters', ['meter_number' => 'MTR-2001'])
            ->assertStatus(403);
    }

    public function test_subscriber_without_completed_profile_cannot_create_meter(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriber-meters', ['meter_number' => 'MTR-3001'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscriber');
    }

    public function test_admin_can_create_meter_for_subscriber(): void
    {
        $admin = $this->makeAdmin();
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/subscriber-meters', [
                'meter_number' => 'MTR-ADMIN-1',
                'user_id' => $subscriberUser->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subscriber_meters', [
            'subscriber_id' => $subscriber->id,
            'meter_number' => 'MTR-ADMIN-1',
        ]);
    }

    public function test_admin_creating_meter_without_user_id_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/subscriber-meters', ['meter_number' => 'MTR-ADMIN-2'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_admin_creating_meter_for_non_subscriber_user_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $this->actingAs($admin)
            ->postJson('/api/v1/subscriber-meters', [
                'meter_number' => 'MTR-ADMIN-3',
                'user_id' => $owner->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_subscriber_cannot_send_user_id_when_creating_meter(): void
    {
        [, $subscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/subscriber-meters', [
                'meter_number' => 'MTR-SELF-1',
                'user_id' => $subscriberUser->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_subscriber_can_view_own_meter(): void
    {
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/subscriber-meters/{$meter->id}")
            ->assertOk();
    }

    public function test_subscriber_cannot_view_another_subscribers_meter(): void
    {
        [$subscriber] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        [, $otherSubscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($otherSubscriberUser)
            ->getJson("/api/v1/subscriber-meters/{$meter->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_view_any_meter(): void
    {
        $admin = $this->makeAdmin();
        [$subscriber] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $this->actingAs($admin)
            ->getJson("/api/v1/subscriber-meters/{$meter->id}")
            ->assertOk();
    }

    public function test_subscriber_can_update_own_meter(): void
    {
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $this->actingAs($subscriberUser)
            ->patchJson("/api/v1/subscriber-meters/{$meter->id}", ['property_label' => 'شقة جديدة'])
            ->assertOk();

        $this->assertSame('شقة جديدة', $meter->fresh()->property_label);
    }

    public function test_subscriber_cannot_update_another_subscribers_meter(): void
    {
        [$subscriber] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        [, $otherSubscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($otherSubscriberUser)
            ->patchJson("/api/v1/subscriber-meters/{$meter->id}", ['property_label' => 'محاولة'])
            ->assertStatus(403);
    }

    public function test_admin_can_update_any_meter(): void
    {
        $admin = $this->makeAdmin();
        [$subscriber] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/subscriber-meters/{$meter->id}", ['property_label' => 'تعديل الأدمن'])
            ->assertOk();

        $this->assertSame('تعديل الأدمن', $meter->fresh()->property_label);
    }

    public function test_meter_number_uniqueness_ignores_self_on_update(): void
    {
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id, 'meter_number' => 'MTR-SELF']);

        $this->actingAs($subscriberUser)
            ->patchJson("/api/v1/subscriber-meters/{$meter->id}", ['meter_number' => 'MTR-SELF'])
            ->assertOk();
    }

    public function test_invalid_status_value_is_rejected(): void
    {
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $this->actingAs($subscriberUser)
            ->patchJson("/api/v1/subscriber-meters/{$meter->id}", ['status' => 'suspended'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_subscriber_can_delete_meter_without_contracts(): void
    {
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $this->actingAs($subscriberUser)
            ->deleteJson("/api/v1/subscriber-meters/{$meter->id}")
            ->assertOk();

        $this->assertSoftDeleted('subscriber_meters', ['id' => $meter->id]);
    }

    public function test_cannot_delete_meter_with_active_subscription(): void
    {
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $this->actingAs($subscriberUser)
            ->deleteJson("/api/v1/subscriber-meters/{$meter->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('meter');

        $this->assertDatabaseHas('subscriber_meters', ['id' => $meter->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_meter_with_pending_subscription(): void
    {
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
        ]);

        $this->actingAs($subscriberUser)
            ->deleteJson("/api/v1/subscriber-meters/{$meter->id}")
            ->assertStatus(422);
    }

    public function test_admin_can_delete_any_meter(): void
    {
        $admin = $this->makeAdmin();
        [$subscriber] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/subscriber-meters/{$meter->id}")
            ->assertOk();
    }

    public function test_subscriber_cannot_delete_another_subscribers_meter(): void
    {
        [$subscriber] = $this->makeSubscriberUser();
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        [, $otherSubscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($otherSubscriberUser)
            ->deleteJson("/api/v1/subscriber-meters/{$meter->id}")
            ->assertStatus(403);
    }

    public function test_index_scoped_to_own_meters_only(): void
    {
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $ownMeter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        [$otherSubscriber] = $this->makeSubscriberUser();
        SubscriberMeter::factory()->create(['subscriber_id' => $otherSubscriber->id]);

        $response = $this->actingAs($subscriberUser)
            ->getJson('/api/v1/subscriber-meters');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($ownMeter->id));
        $this->assertCount(1, $ids);
    }

    public function test_unauthenticated_user_cannot_access_subscriber_meters(): void
    {
        $this->getJson('/api/v1/subscriber-meters')->assertStatus(401);
    }

    public function test_cannot_deactivate_meter_with_active_subscription(): void
    {
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id, 'status' => 'active']);

        $generator = Generator::factory()->create();
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'status' => 'active',
        ]);

        $this->actingAs($subscriberUser)
            ->patchJson("/api/v1/subscriber-meters/{$meter->id}", ['status' => 'inactive'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('meter');

        $this->assertSame('active', $meter->fresh()->status->value);
    }

    public function test_can_deactivate_meter_without_active_subscription(): void
    {
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id, 'status' => 'active']);

        $this->actingAs($subscriberUser)
            ->patchJson("/api/v1/subscriber-meters/{$meter->id}", ['status' => 'inactive'])
            ->assertOk();

        $this->assertSame('inactive', $meter->fresh()->status->value);
    }

    public function test_updating_other_fields_without_changing_status_is_unaffected_by_active_subscription(): void
    {
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id, 'status' => 'active']);

        $generator = Generator::factory()->create();
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'status' => 'active',
        ]);

        $this->actingAs($subscriberUser)
            ->patchJson("/api/v1/subscriber-meters/{$meter->id}", ['property_label' => 'شقة 5'])
            ->assertOk();

        $this->assertSame('شقة 5', $meter->fresh()->property_label);
    }
}
