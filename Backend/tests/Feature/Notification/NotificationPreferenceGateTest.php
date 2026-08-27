<?php

namespace Tests\Feature\Notification;

use App\Enums\Role as RoleEnum;
use App\Models\Complaint;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\ComplaintResolvedNotification;
use App\Notifications\FaultReportedNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationPreferenceGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_owner_default_preferences_include_notification_types(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $response = $this->actingAs($owner)->getJson('/api/v1/preferences');

        $response->assertOk();
        $this->assertTrue($response->json('data.notify_fault_reported'));
        $this->assertArrayNotHasKey('notify_complaint_resolved', $response->json('data'));
    }

    public function test_notification_is_sent_when_preference_enabled_by_default(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $fault = Fault::create([
            'generator_id' => $generator->id,
            'source' => 'manual',
            'title' => 'عطل تجريبي',
            'description' => 'وصف.',
            'priority' => 'medium',
            'status' => 'pending_verification',
            'reported_at' => now(),
        ]);

        event(new \App\Events\FaultReported($fault));

        Notification::assertSentTo($owner, FaultReportedNotification::class);
    }

    public function test_notification_is_not_sent_when_preference_disabled(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        UserPreference::create([
            'user_id' => $owner->id,
            'key' => 'notify_fault_reported',
            'value' => '0',
        ]);

        $fault = Fault::create([
            'generator_id' => $generator->id,
            'source' => 'manual',
            'title' => 'عطل تجريبي',
            'description' => 'وصف.',
            'priority' => 'medium',
            'status' => 'pending_verification',
            'reported_at' => now(),
        ]);

        event(new \App\Events\FaultReported($fault));

        Notification::assertNotSentTo($owner, FaultReportedNotification::class);
    }

    public function test_owner_can_disable_specific_notification_type_via_api(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->patchJson('/api/v1/preferences', [
                'preferences' => ['notify_fault_reported' => false],
            ])
            ->assertOk()
            ->assertJsonPath('data.notify_fault_reported', false);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $owner->id,
            'key' => 'notify_fault_reported',
            'value' => '0',
        ]);
    }

    public function test_subscriber_default_preferences_do_not_include_owner_only_types(): void
    {
        $subscriber = User::factory()->create();
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        $response = $this->actingAs($subscriber)->getJson('/api/v1/preferences');

        $response->assertOk();
        $this->assertArrayHasKey('notify_complaint_resolved', $response->json('data'));
        $this->assertArrayNotHasKey('notify_fault_reported', $response->json('data'));
    }
}
