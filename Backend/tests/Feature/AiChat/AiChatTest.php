<?php

namespace Tests\Feature\AiChat;

use App\Contracts\AiChatProviderContract;
use App\Enums\Role as RoleEnum;
use App\Exceptions\AiProviderException;
use App\Models\Fault;
use App\Models\FaultPrediction;
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
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);

        $this->bindAiProvider(fn() => 'رد تجريبي من المساعد الذكي بخصوص المولد.');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    private function bindAiProvider(\Closure $replyFactory, bool $throws = false): void
    {
        $mock = Mockery::mock(AiChatProviderContract::class);

        if ($throws) {
            $mock->shouldReceive('reply')->andThrow(new AiProviderException('service unavailable'));
        } else {
            $mock->shouldReceive('reply')->andReturnUsing($replyFactory);
        }

        $this->app->instance(AiChatProviderContract::class, $mock);
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

    private function postSession(User $actingAs, array $payload)
    {
        return $this->actingAs($actingAs)
            ->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/ai-chat/sessions', $payload);
    }

    private function postMessage(User $actingAs, $sessionId, array $payload)
    {
        return $this->actingAs($actingAs)
            ->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/messages", $payload);
    }

    /**
     * @return array{0: User, 1: Generator}
     */
    private function makeConnectedSubscriber(): array
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
            'billing_cycle' => 'monthly',
        ]);

        return [$subscriberUser, $generator];
    }

    public function test_subscriber_can_start_chat_about_own_generator(): void
    {
        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();

        $response = $this->postSession($subscriberUser, ['generator_id' => $generator->id]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ai_chat_sessions', [
            'user_id' => $subscriberUser->id,
            'generator_id' => $generator->id,
            'context_type' => 'subscriber_support',
        ]);
    }

    public function test_subscriber_cannot_start_chat_about_unrelated_generator(): void
    {
        $otherOwner = $this->makeOwner();
        $unrelatedGenerator = Generator::factory()->create(['owner_id' => $otherOwner->id]);

        $randomSubscriberUser = User::factory()->create();
        $randomSubscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $randomSubscriberUser->id]);

        $this->postSession($randomSubscriberUser, ['generator_id' => $unrelatedGenerator->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('generator_id');
    }

    public function test_owner_can_start_chat_about_own_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $response = $this->postSession($owner, ['generator_id' => $generator->id]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ai_chat_sessions', [
            'user_id' => $owner->id,
            'generator_id' => $generator->id,
            'context_type' => 'owner_diagnostic',
        ]);
    }

    public function test_linked_technician_can_start_chat(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
        ]);
        $technician->generators()->attach($generator->id);

        $this->postSession($technicianUser, ['generator_id' => $generator->id])
            ->assertStatus(201);
    }

    public function test_admin_can_start_chat_about_any_active_generator(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        $this->postSession($admin, ['generator_id' => $generator->id])
            ->assertStatus(201);
    }

    public function test_starting_session_with_initial_message_gets_ai_reply(): void
    {
        $this->bindAiProvider(fn() => 'يبدو أن المشكلة بفلتر الهواء، جربي تنظيفه.');

        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();

        $response = $this->postSession($subscriberUser, [
            'generator_id' => $generator->id,
            'message' => 'المولد بيصدر صوت غريب وقت التشغيل.',
        ]);

        $response->assertStatus(201);
        $messages = $response->json('data.messages');
        $this->assertCount(2, $messages);
        $this->assertSame('user', $messages[0]['role']);
        $this->assertSame('assistant', $messages[1]['role']);
        $this->assertSame('يبدو أن المشكلة بفلتر الهواء، جربي تنظيفه.', $messages[1]['content']);
    }

    public function test_participant_can_send_followup_message(): void
    {
        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();
        $sessionId = $this->postSession($subscriberUser, ['generator_id' => $generator->id])
            ->json('data.id');

        $this->postMessage($subscriberUser, $sessionId, [
            'message' => 'كيف بدي أفحص مستوى الزيت؟',
        ])
            ->assertStatus(201);

        $this->assertDatabaseHas('ai_chat_messages', [
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => 'كيف بدي أفحص مستوى الزيت؟',
        ]);
    }

    public function test_non_owner_cannot_send_message_to_others_session(): void
    {
        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();
        $sessionId = $this->postSession($subscriberUser, ['generator_id' => $generator->id])
            ->json('data.id');

        $otherUser = User::factory()->create();
        $otherUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $otherUser->id]);

        $this->postMessage($otherUser, $sessionId, ['message' => 'محاولة تطفل'])
            ->assertStatus(403);
    }

    public function test_ai_provider_failure_returns_graceful_fallback_message(): void
    {
        $this->bindAiProvider(fn() => '', throws: true);

        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();
        $sessionId = $this->postSession($subscriberUser, ['generator_id' => $generator->id])
            ->json('data.id');

        $response = $this->postMessage($subscriberUser, $sessionId, ['message' => 'سؤال؟']);

        $response->assertStatus(201);
        $this->assertStringContainsString('تعذّر', $response->json('data.content'));
    }

    public function test_available_generators_scoped_to_subscriber_subscriptions(): void
    {
        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();

        $otherOwner = $this->makeOwner();
        Generator::factory()->create(['owner_id' => $otherOwner->id]);

        $response = $this->actingAs($subscriberUser)
            ->getJson('/api/v1/ai-chat/available-generators');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertSame([$generator->id], $ids->all());
    }

    public function test_submit_chat_as_prediction_creates_fault_prediction_with_source_chat(): void
    {
        $this->bindAiProvider(fn() => 'يبدو عطل باحتراق الوقود، ينصح بفحص الحاقن.');

        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $sessionId = $this->postSession($owner, [
            'generator_id' => $generator->id,
            'message' => 'في دخان أسود كثير.',
        ])
            ->json('data.id');

        $response = $this->actingAs($owner)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-prediction");

        $response->assertStatus(201);
        $this->assertDatabaseHas('fault_predictions', [
            'ai_chat_session_id' => $sessionId,
            'source' => 'chat',
            'generator_id' => $generator->id,
            'recommendation' => 'يبدو عطل باحتراق الوقود، ينصح بفحص الحاقن.',
        ]);
    }

    public function test_cannot_submit_same_session_twice(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $sessionId = $this->postSession($owner, [
            'generator_id' => $generator->id,
            'message' => 'سؤال.',
        ])
            ->json('data.id');

        $this->actingAs($owner)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-prediction")
            ->assertStatus(201);

        $this->actingAs($owner)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-prediction")
            ->assertStatus(422);

        $this->assertSame(1, FaultPrediction::where('ai_chat_session_id', $sessionId)->count());
    }

    public function test_cannot_submit_empty_session(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $sessionId = $this->postSession($owner, ['generator_id' => $generator->id])
            ->json('data.id');

        $this->actingAs($owner)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-prediction")
            ->assertStatus(422);
    }

    public function test_non_owner_cannot_submit_someone_elses_session(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $sessionId = $this->postSession($owner, [
            'generator_id' => $generator->id,
            'message' => 'سؤال.',
        ])
            ->json('data.id');

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-prediction")
            ->assertStatus(403);
    }

    public function test_reviewing_owner_can_confirm_chat_sourced_prediction(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $sessionId = $this->postSession($owner, [
            'generator_id' => $generator->id,
            'message' => 'المولد متوقف كليًا.',
        ])
            ->json('data.id');

        $this->actingAs($owner)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-prediction")
            ->assertStatus(201);

        $prediction = FaultPrediction::where('ai_chat_session_id', $sessionId)->firstOrFail();

        $this->actingAs($owner)
            ->patchJson("/api/v1/fault-predictions/{$prediction->id}/confirm")
            ->assertStatus(201);

        $this->assertDatabaseHas('faults', ['fault_prediction_id' => $prediction->id]);
    }

    public function test_owner_cannot_submit_diagnostic_session_as_fault_report(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $sessionId = $this->postSession($owner, [
            'generator_id' => $generator->id,
            'message' => 'سؤال تشخيصي.',
        ])
            ->json('data.id');

        $this->actingAs($owner)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-fault-report")
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_submit_support_session_as_prediction(): void
    {
        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();

        $sessionId = $this->postSession($subscriberUser, [
            'generator_id' => $generator->id,
            'message' => 'في مشكلة بالكهرباء عندي بالبيت.',
        ])
            ->json('data.id');

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-prediction")
            ->assertStatus(403);
    }

    public function test_subscriber_can_submit_chat_as_fault_report(): void
    {
        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();

        $sessionId = $this->postSession($subscriberUser, [
            'generator_id' => $generator->id,
            'message' => 'في مشكلة بالكهرباء عندي بالبيت من الصبح.',
        ])
            ->json('data.id');

        $response = $this->actingAs($subscriberUser)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-fault-report");

        $response->assertStatus(201);
        $this->assertDatabaseHas('faults', [
            'ai_chat_session_id' => $sessionId,
            'generator_id' => $generator->id,
            'source' => 'subscriber_report',
            'reported_by' => $subscriberUser->id,
        ]);
    }

    public function test_subscriber_cannot_submit_fault_report_twice(): void
    {
        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();

        $sessionId = $this->postSession($subscriberUser, [
            'generator_id' => $generator->id,
            'message' => 'مشكلة بالكهرباء.',
        ])
            ->json('data.id');

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-fault-report")
            ->assertStatus(201);

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-fault-report")
            ->assertStatus(422);

        $this->assertSame(1, Fault::where('ai_chat_session_id', $sessionId)->count());
    }

    public function test_subscriber_cannot_submit_empty_session_as_fault_report(): void
    {
        [$subscriberUser, $generator] = $this->makeConnectedSubscriber();

        $sessionId = $this->postSession($subscriberUser, ['generator_id' => $generator->id])
            ->json('data.id');

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/ai-chat/sessions/{$sessionId}/submit-as-fault-report")
            ->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_access_ai_chat(): void
    {
        $this->getJson('/api/v1/ai-chat/sessions')->assertStatus(401);
    }
}
