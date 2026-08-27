<?php

namespace Tests\Feature\Conversation;

use App\Enums\Role as RoleEnum;
use App\Models\Conversation;
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
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    /**
     * @return array{0: User, 1: User}   
     */
    private function makeRelatedOwnerAndSubscriber(): array
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
        ]);

        return [$owner, $subscriberUser];
    }

    private function makeUnrelatedUsers(): array
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $subscriberUser->id]);

        return [$owner, $subscriberUser];
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    /**
     * @return array{0: User, 1: Technician}  
     */
    private function makePrivateTechnicianForOwner(User $owner): array
    {
        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
        ]);

        return [$technicianUser, $technician];
    }

    public function test_subscriber_can_start_conversation_with_related_owner(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();

        $response = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('conversations', [
            'user1_id' => min($owner->id, $subscriberUser->id),
            'user2_id' => max($owner->id, $subscriberUser->id),
        ]);
    }

    public function test_cannot_start_conversation_with_unrelated_user(): void
    {
        [$owner, $subscriberUser] = $this->makeUnrelatedUsers();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_cannot_start_conversation_with_self(): void
    {
        [, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $subscriberUser->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_starting_same_conversation_twice_does_not_duplicate(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->assertStatus(201);

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->assertStatus(201);

        $this->assertSame(1, Conversation::count());
    }

    public function test_conversation_is_same_regardless_of_who_initiates_first(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();

        $first = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->json('data.id');

        $second = $this->actingAs($owner)
            ->postJson('/api/v1/conversations', ['user_id' => $subscriberUser->id])
            ->json('data.id');

        $this->assertSame($first, $second);
        $this->assertSame(1, Conversation::count());
    }

    public function test_participant_can_view_conversation(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();
        $conversationId = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->json('data.id');

        $this->actingAs($owner)
            ->getJson("/api/v1/conversations/{$conversationId}")
            ->assertOk();
    }

    public function test_non_participant_cannot_view_conversation(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();
        $conversationId = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->json('data.id');

        [, $unrelatedUser] = $this->makeUnrelatedUsers();

        $this->actingAs($unrelatedUser)
            ->getJson("/api/v1/conversations/{$conversationId}")
            ->assertStatus(403);
    }

    public function test_participant_can_send_message(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();
        $conversationId = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->json('data.id');

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/conversations/{$conversationId}/messages", [
                'message_text' => 'مرحبًا، عندي استفسار.',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversationId,
            'sender_id' => $subscriberUser->id,
        ]);
    }

    public function test_non_participant_cannot_send_message(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();
        $conversationId = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->json('data.id');

        [, $unrelatedUser] = $this->makeUnrelatedUsers();

        $this->actingAs($unrelatedUser)
            ->postJson("/api/v1/conversations/{$conversationId}/messages", [
                'message_text' => 'محاولة تطفل.',
            ])
            ->assertStatus(403);
    }

    public function test_deleting_conversation_hides_it_only_for_the_deleting_user(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();
        $conversationId = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->json('data.id');

        $this->actingAs($subscriberUser)
            ->deleteJson("/api/v1/conversations/{$conversationId}")
            ->assertOk();

        $conversation = Conversation::findOrFail($conversationId);

        $this->assertTrue($conversation->isDeletedFor($subscriberUser));
        $this->assertFalse($conversation->isDeletedFor($owner));

        $this->actingAs($owner)
            ->getJson("/api/v1/conversations/{$conversationId}")
            ->assertOk();
    }

    public function test_starting_conversation_again_after_deletion_restores_visibility(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();
        $conversationId = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->json('data.id');

        $this->actingAs($subscriberUser)
            ->deleteJson("/api/v1/conversations/{$conversationId}")
            ->assertOk();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->assertStatus(201);

        $this->assertSame(1, Conversation::count());
        $this->assertFalse(Conversation::findOrFail($conversationId)->isDeletedFor($subscriberUser));
    }

    public function test_unauthenticated_user_cannot_access_conversations(): void
    {
        $this->getJson('/api/v1/conversations')->assertStatus(401);
    }

    public function test_private_technician_can_start_conversation_with_their_owner(): void
    {
        $owner = $this->makeOwnerOnly();
        [$technicianUser] = $this->makePrivateTechnicianForOwner($owner);

        $this->actingAs($technicianUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->assertStatus(201);
    }

    public function test_technician_cannot_start_conversation_with_unrelated_owner(): void
    {
        $owner = $this->makeOwnerOnly();
        $otherOwner = $this->makeOwnerOnly();
        [$technicianUser] = $this->makePrivateTechnicianForOwner($otherOwner);

        $this->actingAs($technicianUser)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_technician_cannot_start_conversation_with_subscriber(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();
        [$technicianUser] = $this->makePrivateTechnicianForOwner($owner);

        $this->actingAs($technicianUser)
            ->postJson('/api/v1/conversations', ['user_id' => $subscriberUser->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_admin_can_start_conversation_with_any_owner(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwnerOnly();

        $this->actingAs($admin)
            ->postJson('/api/v1/conversations', ['user_id' => $owner->id])
            ->assertStatus(201);
    }

    public function test_owner_can_start_conversation_with_admin(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwnerOnly();

        $this->actingAs($owner)
            ->postJson('/api/v1/conversations', ['user_id' => $admin->id])
            ->assertStatus(201);
    }

    public function test_admin_can_start_conversation_with_subscriber_directly(): void
    {
        $admin = $this->makeAdmin();
        [, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();

        $this->actingAs($admin)
            ->postJson('/api/v1/conversations', ['user_id' => $subscriberUser->id])
            ->assertStatus(201);
    }

    public function test_admin_can_start_conversation_with_technician_directly(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwnerOnly();
        [$technicianUser] = $this->makePrivateTechnicianForOwner($owner);

        $this->actingAs($admin)
            ->postJson('/api/v1/conversations', ['user_id' => $technicianUser->id])
            ->assertStatus(201);
    }

    private function makeOwnerOnly(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }

    public function test_technician_can_open_conversation_with_own_owner_without_supplying_any_id(): void
    {
        $owner = $this->makeOwnerOnly();
        [$technicianUser] = $this->makePrivateTechnicianForOwner($owner);

        // No user_id / owner_id is ever sent — the backend resolves the
        // owner purely from the technician's own relationship.
        $response = $this->actingAs($technicianUser)
            ->postJson('/api/v1/conversations/start-with-owner')
            ->assertStatus(201);

        $conversationId = $response->json('data.id');

        $this->assertDatabaseHas('conversations', [
            'id' => $conversationId,
        ]);

        $conversation = \App\Models\Conversation::findOrFail($conversationId);
        $this->assertTrue(
            in_array($owner->id, [$conversation->user1_id, $conversation->user2_id], true)
        );
        $this->assertTrue(
            in_array($technicianUser->id, [$conversation->user1_id, $conversation->user2_id], true)
        );
    }

    public function test_technician_start_with_owner_ignores_any_supplied_owner_id(): void
    {
        $owner = $this->makeOwnerOnly();
        $attackerOwner = $this->makeOwnerOnly();
        [$technicianUser] = $this->makePrivateTechnicianForOwner($owner);

        $response = $this->actingAs($technicianUser)
            ->postJson('/api/v1/conversations/start-with-owner', [
                'user_id' => $attackerOwner->id,
                'owner_id' => $attackerOwner->id,
            ])
            ->assertStatus(201);

        $conversation = \App\Models\Conversation::findOrFail($response->json('data.id'));
        $this->assertTrue(
            in_array($owner->id, [$conversation->user1_id, $conversation->user2_id], true)
        );
        $this->assertFalse(
            in_array($attackerOwner->id, [$conversation->user1_id, $conversation->user2_id], true)
        );
    }

    public function test_non_technician_cannot_use_start_with_owner(): void
    {
        [$owner, $subscriberUser] = $this->makeRelatedOwnerAndSubscriber();

        $this->actingAs($owner)
            ->postJson('/api/v1/conversations/start-with-owner')
            ->assertStatus(403);

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/conversations/start-with-owner')
            ->assertStatus(403);
    }

    public function test_start_with_owner_is_idempotent_and_reuses_the_same_conversation(): void
    {
        $owner = $this->makeOwnerOnly();
        [$technicianUser] = $this->makePrivateTechnicianForOwner($owner);

        $first = $this->actingAs($technicianUser)
            ->postJson('/api/v1/conversations/start-with-owner')
            ->assertStatus(201)
            ->json('data.id');

        $second = $this->actingAs($technicianUser)
            ->postJson('/api/v1/conversations/start-with-owner')
            ->assertStatus(201)
            ->json('data.id');

        $this->assertEquals($first, $second);
    }
}
