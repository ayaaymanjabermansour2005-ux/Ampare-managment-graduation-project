<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createNotificationFor(User $user, array $data = [], bool $read = false): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(array_merge([
                'title' => 'إشعار تجريبي',
                'message' => 'رسالة تجريبية.',
            ], $data)),
            'read_at' => $read ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_user_can_list_own_notifications(): void
    {
        $user = User::factory()->create();
        $this->createNotificationFor($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_index_scoped_to_own_notifications_only(): void
    {
        $user = User::factory()->create();
        $this->createNotificationFor($user);

        $otherUser = User::factory()->create();
        $this->createNotificationFor($otherUser);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_unread_only_filter_excludes_read_notifications(): void
    {
        $user = User::factory()->create();
        $unreadId = $this->createNotificationFor($user, [], read: false);
        $this->createNotificationFor($user, [], read: true);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications?unread_only=1');

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertSame([$unreadId], $ids->all());
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $id = $this->createNotificationFor($user);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/notifications/{$id}/read");

        $response->assertOk();
        $this->assertTrue($response->json('data.is_read'));
        $this->assertDatabaseHas('notifications', ['id' => $id]);
        $this->assertNotNull(DB::table('notifications')->where('id', $id)->value('read_at'));
    }

    public function test_marking_nonexistent_notification_as_read_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/notifications/'.Str::uuid().'/read')
            ->assertStatus(404);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignId = $this->createNotificationFor($otherUser);

        $this->actingAs($user)
            ->patchJson("/api/v1/notifications/{$foreignId}/read")
            ->assertStatus(404);

        $this->assertNull(DB::table('notifications')->where('id', $foreignId)->value('read_at'));
    }

    public function test_mark_all_as_read_only_affects_own_unread_notifications(): void
    {
        $user = User::factory()->create();
        $this->createNotificationFor($user, [], read: false);
        $this->createNotificationFor($user, [], read: false);

        $otherUser = User::factory()->create();
        $foreignUnread = $this->createNotificationFor($otherUser, [], read: false);

        $this->actingAs($user)
            ->patchJson('/api/v1/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count());

        $this->assertNull(DB::table('notifications')->where('id', $foreignUnread)->value('read_at'));
    }

    public function test_user_can_delete_own_notification(): void
    {
        $user = User::factory()->create();
        $id = $this->createNotificationFor($user);

        $this->actingAs($user)
            ->deleteJson("/api/v1/notifications/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('notifications', ['id' => $id]);
    }

    public function test_user_cannot_delete_another_users_notification(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignId = $this->createNotificationFor($otherUser);

        $this->actingAs($user)
            ->deleteJson("/api/v1/notifications/{$foreignId}")
            ->assertStatus(422);

        $this->assertDatabaseHas('notifications', ['id' => $foreignId]);
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }
}
