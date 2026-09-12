<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_admin_can_send_an_announcement_to_a_specific_audience(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        $subscribers = User::factory()->count(3)->create();
        foreach ($subscribers as $subscriber) {
            $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);
        }

        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/announcements', [
            'title' => 'صيانة مجدولة',
            'message' => 'سيتم إجراء صيانة على المنصة الليلة.',
            'audience' => 'subscriber',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.recipients_count', 3);
        Notification::assertSentTo($subscribers, AdminAnnouncementNotification::class);
        Notification::assertNotSentTo($owner, AdminAnnouncementNotification::class);
    }

    public function test_announcement_requires_title_message_and_a_valid_audience(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/announcements', [
            'title' => '',
            'message' => '',
            'audience' => 'not-a-real-audience',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'message', 'audience']);
    }

    public function test_non_admin_cannot_send_an_announcement(): void
    {
        $subscriber = User::factory()->create();
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        $response = $this->actingAs($subscriber)->postJson('/api/v1/admin/announcements', [
            'title' => 'صيانة مجدولة',
            'message' => 'سيتم إجراء صيانة على المنصة الليلة.',
            'audience' => 'all',
        ]);

        $response->assertStatus(403);
    }
}
