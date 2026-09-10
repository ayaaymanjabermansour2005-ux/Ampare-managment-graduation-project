<?php

namespace Tests\Feature\Attachment;

use App\Contracts\AiChatProviderContract;
use App\Enums\Role as RoleEnum;
use App\Models\AiChatMessage;
use App\Models\Complaint;
use App\Models\Conversation;
use App\Models\Generator;
use App\Models\Message;
use App\Models\MeterReading;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AttachmentExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        Storage::fake('attachments');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
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

    private function bindAiProvider(): void
    {
        $mock = Mockery::mock(AiChatProviderContract::class);
        $mock->shouldReceive('reply')->andReturn('رد تجريبي.');
        $this->app->instance(AiChatProviderContract::class, $mock);
    }

    public function test_technician_can_attach_photo_to_meter_reading(): void
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

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
        ]);

        $reading = MeterReading::create([
            'subscription_id' => $subscription->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'created_by' => $technicianUser->id,
        ]);

        $response = $this->actingAs($technicianUser)
            ->postJson("/api/v1/meter-readings/{$reading->id}/attachments", [
                'file' => UploadedFile::fake()->image('reading.jpg'),
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => MeterReading::class,
            'attachable_id' => $reading->id,
            'document_type' => 'meter_reading_photo',
        ]);
    }

    public function test_unrelated_technician_cannot_attach_to_meter_reading(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
        ]);

        $reading = MeterReading::create([
            'subscription_id' => $subscription->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'created_by' => $owner->id,
        ]);

        $unrelatedTechnicianUser = User::factory()->create();
        $unrelatedTechnicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        Technician::factory()->create([
            'user_id' => $unrelatedTechnicianUser->id,
            'owner_id' => $this->makeOwner()->id,
        ]);

        $this->actingAs($unrelatedTechnicianUser)
            ->postJson("/api/v1/meter-readings/{$reading->id}/attachments", [
                'file' => UploadedFile::fake()->image('reading.jpg'),
            ])
            ->assertStatus(403);
    }

    public function test_submitter_can_attach_image_to_own_complaint(): void
    {
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $subscriberUser->id]);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'subject' => 'شكوى تجريبية',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($subscriberUser)
            ->postJson("/api/v1/complaints/{$complaint->id}/attachments", [
                'file' => UploadedFile::fake()->image('proof.jpg'),
                'document_type' => 'complaint_image',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Complaint::class,
            'attachable_id' => $complaint->id,
        ]);
    }

    public function test_unrelated_user_cannot_attach_to_others_complaint(): void
    {
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $subscriberUser->id]);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'subject' => 'شكوى تجريبية',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $otherUser = User::factory()->create();
        $otherUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($otherUser)
            ->postJson("/api/v1/complaints/{$complaint->id}/attachments", [
                'file' => UploadedFile::fake()->image('proof.jpg'),
                'document_type' => 'complaint_image',
            ])
            ->assertStatus(403);
    }

    public function test_submitter_can_list_own_complaint_attachments(): void
    {
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $subscriberUser->id]);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'subject' => 'شكوى تجريبية',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/complaints/{$complaint->id}/attachments", [
                'file' => UploadedFile::fake()->image('proof.jpg'),
                'document_type' => 'complaint_image',
            ])
            ->assertStatus(201);

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/complaints/{$complaint->id}/attachments")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_list_any_complaint_attachments(): void
    {
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $subscriberUser->id]);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'subject' => 'شكوى تجريبية',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/complaints/{$complaint->id}/attachments", [
                'file' => UploadedFile::fake()->image('proof.jpg'),
                'document_type' => 'complaint_image',
            ])
            ->assertStatus(201);

        $this->actingAs($this->makeAdmin())
            ->getJson("/api/v1/complaints/{$complaint->id}/attachments")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_unrelated_user_cannot_list_others_complaint_attachments(): void
    {
        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $subscriberUser->id]);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'subject' => 'شكوى تجريبية',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $otherUser = User::factory()->create();
        $otherUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($otherUser)
            ->getJson("/api/v1/complaints/{$complaint->id}/attachments")
            ->assertStatus(403);
    }

    public function test_subscriber_can_attach_image_when_starting_ai_chat(): void
    {
        $this->bindAiProvider();

        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
        ]);

        $response = $this->actingAs($subscriberUser)
            ->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->post('/api/v1/ai-chat/sessions', [
                'generator_id' => $generator->id,
                'message' => 'شو هاد الصوت الغريب؟',
                'attachment' => UploadedFile::fake()->image('sound-source.jpg'),
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => AiChatMessage::class,
            'document_type' => 'chat_attachment',
        ]);
    }

    public function test_participant_can_send_message_with_attachment_only_no_text(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
        ]);

        $userIds = [$owner->id, $subscriberUser->id];
        sort($userIds);
        $conversation = Conversation::create(['user1_id' => $userIds[0], 'user2_id' => $userIds[1]]);

        $response = $this->actingAs($subscriberUser)
            ->post("/api/v1/conversations/{$conversation->id}/messages", [
                'attachments' => [UploadedFile::fake()->image('receipt.jpg')],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Message::class,
            'document_type' => 'message_attachment',
        ]);
    }

    public function test_message_without_text_or_attachments_is_rejected(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
        ]);

        $userIds = [$owner->id, $subscriberUser->id];
        sort($userIds);
        $conversation = Conversation::create(['user1_id' => $userIds[0], 'user2_id' => $userIds[1]]);

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/conversations/{$conversation->id}/messages", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message_text');
    }
}
