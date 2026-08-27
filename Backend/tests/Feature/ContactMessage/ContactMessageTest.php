<?php

namespace Tests\Feature\ContactMessage;

use App\Enums\Role as RoleEnum;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'أحمد محمد',
            'phone' => '0599123456',
            'email' => 'ahmad@example.com',
            'subject' => 'general',
            'message' => 'رسالة تجريبية للتواصل.',
        ], $overrides);
    }

    public function test_guest_can_submit_contact_message(): void
    {
        Notification::fake();

        $admin = $this->makeAdmin();

        $response = $this->postJson('/api/v1/public/contact-messages', $this->validPayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('contact_messages', [
            'email' => 'ahmad@example.com',
            'status' => 'new',
        ]);

        Notification::assertSentTo($admin, NewContactMessageNotification::class);
    }

    public function test_contact_message_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/public/contact-messages', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'email', 'subject', 'message']);
    }

    public function test_contact_message_store_rejects_invalid_subject(): void
    {
        $response = $this->postJson(
            '/api/v1/public/contact-messages',
            $this->validPayload(['subject' => 'not-a-real-subject'])
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['subject']);
    }

    public function test_contact_message_submission_is_rate_limited(): void
    {
        Cache::flush();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/public/contact-messages', $this->validPayload([
                'email' => "guest{$i}@example.com",
            ]))->assertStatus(201);
        }

        $this->postJson('/api/v1/public/contact-messages', $this->validPayload([
            'email' => 'overflow@example.com',
        ]))->assertStatus(429);
    }

    public function test_guest_cannot_list_contact_messages(): void
    {
        $this->getJson('/api/v1/admin/contact-messages')->assertStatus(401);
    }

    public function test_non_admin_cannot_list_contact_messages(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/admin/contact-messages')
            ->assertStatus(403);
    }

    public function test_admin_can_list_contact_messages(): void
    {
        $admin = $this->makeAdmin();
        ContactMessage::create($this->validPayload());

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/contact-messages');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_admin_can_view_contact_message_details(): void
    {
        $admin = $this->makeAdmin();
        $message = ContactMessage::create($this->validPayload());

        $response = $this->actingAs($admin)->getJson("/api/v1/admin/contact-messages/{$message->id}");

        $response->assertOk();
        $this->assertSame($message->id, $response->json('data.id'));
    }

    public function test_non_admin_cannot_view_contact_message_details(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $message = ContactMessage::create($this->validPayload());

        $this->actingAs($owner)
            ->getJson("/api/v1/admin/contact-messages/{$message->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_update_contact_message_status(): void
    {
        $admin = $this->makeAdmin();
        $message = ContactMessage::create($this->validPayload());

        $response = $this->actingAs($admin)->patchJson(
            "/api/v1/admin/contact-messages/{$message->id}/status",
            ['status' => 'in_progress', 'admin_note' => 'قيد المتابعة.']
        );

        $response->assertOk();
        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'status' => 'in_progress',
            'admin_note' => 'قيد المتابعة.',
            'handled_by' => $admin->id,
        ]);
    }

    public function test_update_status_rejects_invalid_status_value(): void
    {
        $admin = $this->makeAdmin();
        $message = ContactMessage::create($this->validPayload());

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/contact-messages/{$message->id}/status", ['status' => 'archived'])
            ->assertStatus(422);
    }

    public function test_non_admin_cannot_update_contact_message_status(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $message = ContactMessage::create($this->validPayload());

        $this->actingAs($owner)
            ->patchJson("/api/v1/admin/contact-messages/{$message->id}/status", ['status' => 'resolved'])
            ->assertStatus(403);
    }
}
