<?php

namespace Tests\Feature\FaultPrediction;

use App\Enums\Role as RoleEnum;
use App\Models\FaultPrediction;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaultPredictionTest extends TestCase
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

    private function makeSubscriberUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function validPayload(int $generatorId, array $overrides = []): array
    {
        return array_merge([
            'generator_id' => $generatorId,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.85,
            'recommendation' => 'فحص نظام التبريد قريبًا.',
        ], $overrides);
    }

    public function test_admin_can_create_fault_prediction(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/fault-predictions', $this->validPayload($generator->id));

        $response->assertStatus(201);
        $this->assertSame('pending', $response->json('data.status'));
        $this->assertDatabaseHas('fault_predictions', [
            'generator_id' => $generator->id,
            'is_actual_fault' => false,
        ]);
    }

    public function test_owner_cannot_create_fault_prediction(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson('/api/v1/fault-predictions', $this->validPayload($generator->id))
            ->assertStatus(403);
    }

    public function test_confidence_must_be_between_zero_and_one(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($admin)
            ->postJson('/api/v1/fault-predictions', $this->validPayload($generator->id, ['confidence' => 1.5]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('confidence');
    }

    public function test_generator_id_must_exist(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/fault-predictions', $this->validPayload(999999))
            ->assertStatus(422)
            ->assertJsonValidationErrors('generator_id');
    }

    public function test_admin_can_view_any_prediction(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.7,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->getJson("/api/v1/fault-predictions/{$prediction->id}")
            ->assertOk();
    }

    public function test_owner_can_view_own_generator_prediction(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.7,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/fault-predictions/{$prediction->id}")
            ->assertOk();
    }

    public function test_owner_cannot_view_another_owners_prediction(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $otherOwner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.7,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/fault-predictions/{$prediction->id}")
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_view_any_prediction(): void
    {
        $subscriberUser = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.7,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/fault-predictions/{$prediction->id}")
            ->assertStatus(403);
    }

    public function test_owner_can_confirm_own_generator_prediction(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.9,
            'recommendation' => 'فحص التبريد.',
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/fault-predictions/{$prediction->id}/confirm");

        $response->assertStatus(201);
        $this->assertDatabaseHas('faults', [
            'generator_id' => $generator->id,
            'fault_prediction_id' => $prediction->id,
            'source' => 'ai_prediction',
            'priority' => 'high',
        ]);
    }

    public function test_confirming_updates_prediction_status_and_actual_fault_flag(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.9,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/fault-predictions/{$prediction->id}/confirm")
            ->assertStatus(201);

        $fresh = $prediction->fresh();
        $this->assertSame('confirmed', $fresh->status->value);
        $this->assertTrue($fresh->is_actual_fault);
    }

    public function test_admin_can_confirm_any_prediction(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.9,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/fault-predictions/{$prediction->id}/confirm")
            ->assertStatus(201);
    }

    public function test_owner_cannot_confirm_another_owners_prediction(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $otherOwner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.9,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/fault-predictions/{$prediction->id}/confirm")
            ->assertStatus(403);

        $this->assertDatabaseMissing('faults', ['fault_prediction_id' => $prediction->id]);
    }

    public function test_owner_can_dismiss_own_prediction(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.4,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/fault-predictions/{$prediction->id}/dismiss")
            ->assertOk();

        $fresh = $prediction->fresh();
        $this->assertSame('dismissed', $fresh->status->value);
        $this->assertFalse($fresh->is_actual_fault);
        $this->assertDatabaseMissing('faults', ['fault_prediction_id' => $prediction->id]);
    }

    public function test_owner_cannot_dismiss_another_owners_prediction(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $otherOwner->id]);
        $prediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.4,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/fault-predictions/{$prediction->id}/dismiss")
            ->assertStatus(403);
    }

    public function test_index_scoped_to_owner_generators(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $ownPrediction = FaultPrediction::create([
            'generator_id' => $generator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.5,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $otherOwner = $this->makeOwner();
        $otherGenerator = Generator::factory()->create(['owner_id' => $otherOwner->id]);
        FaultPrediction::create([
            'generator_id' => $otherGenerator->id,
            'prediction_type' => 'engine_overheat',
            'confidence' => 0.5,
            'is_actual_fault' => false,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)
            ->getJson('/api/v1/fault-predictions');

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($ownPrediction->id));
        $this->assertCount(1, $ids);
    }

    public function test_subscriber_cannot_list_predictions(): void
    {
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->getJson('/api/v1/fault-predictions')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_fault_predictions(): void
    {
        $this->getJson('/api/v1/fault-predictions')->assertStatus(401);
    }
}
