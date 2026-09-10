<?php

namespace Tests\Feature\Message;

use App\Enums\Role as RoleEnum;
use App\Models\Conversation;
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

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    /**
     * @return array{0: User, 1: User, 2: Conversation} [owner, subscriberUser, conversation]
     */
    private function makeConversation(): array
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

        $userIds = [$owner->id, $subscriberUser->id];
        sort($userIds);

        $conversation = Conversation::create([
            'user1_id' => $userIds[0],
            'user2_id' => $userIds[1],
        ]);

        return [$owner, $subscriberUser, $conversation];
    }

    public function test_participant_can_list_messages_newest_first(): void
    {
        [$owner, $subscriberUser, $conversation] = $this->makeConversation();

        $first = $conversation->messages()->create([
            'sender_id' => $subscriberUser->id,
            'message_text' => 'أول رسالة',
            'is_read' => false,
        ]);
        $second = $conversation->messages()->create([
            'sender_id' => $owner->id,
            'message_text' => 'ثاني رسالة',
            'is_read' => false,
        ]);

        $response = $this->actingAs($owner)
            ->getJson("/api/v1/conversations/{$conversation->id}/messages");

        // FIX (تدقيق شامل — D1): الصفحة الأولى (بلا page بالطلب) يجب أن
        // تُرجع أحدث الرسائل أولًا حتى لا تُصبح آخر رسالة في محادثة تتجاوز
        // حجم الصفحة الافتراضي غير قابلة للوصول أبدًا من الواجهة. الاستجابة
        // أيضًا مُغلَّفة الآن بـ meta/links (باجيناشن حقيقية) بدل مصفوفة مسطّحة.
        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertSame([$second->id, $first->id], $ids->all());
        $this->assertSame(1, $response->json('data.meta.current_page'));
    }

    public function test_non_participant_cannot_list_messages(): void
    {
        [,, $conversation] = $this->makeConversation();

        $otherUser = User::factory()->create();
        $otherUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($otherUser)
            ->getJson("/api/v1/conversations/{$conversation->id}/messages")
            ->assertStatus(403);
    }

    public function test_message_text_is_required(): void
    {
        [$owner,, $conversation] = $this->makeConversation();

        $this->actingAs($owner)
            ->postJson("/api/v1/conversations/{$conversation->id}/messages", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message_text');
    }

    public function test_message_text_max_length_is_enforced(): void
    {
        [$owner,, $conversation] = $this->makeConversation();

        $this->actingAs($owner)
            ->postJson("/api/v1/conversations/{$conversation->id}/messages", [
                'message_text' => str_repeat('a', 2001),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message_text');
    }

    public function test_sending_message_touches_conversation_timestamp(): void
    {
        [$owner,, $conversation] = $this->makeConversation();
        $originalUpdatedAt = $conversation->updated_at;

        $this->travel(1)->minutes();

        $this->actingAs($owner)
            ->postJson("/api/v1/conversations/{$conversation->id}/messages", [
                'message_text' => 'رسالة جديدة',
            ])
            ->assertStatus(201);

        $this->assertTrue($conversation->fresh()->updated_at->greaterThan($originalUpdatedAt));
    }

    public function test_viewing_messages_marks_others_messages_as_read_but_not_own(): void
    {
        [$owner, $subscriberUser, $conversation] = $this->makeConversation();

        $fromSubscriber = $conversation->messages()->create([
            'sender_id' => $subscriberUser->id,
            'message_text' => 'من المشترك',
            'is_read' => false,
        ]);
        $fromOwner = $conversation->messages()->create([
            'sender_id' => $owner->id,
            'message_text' => 'من الأونر',
            'is_read' => false,
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/conversations/{$conversation->id}/messages")
            ->assertOk();

        $this->assertTrue((bool) $fromSubscriber->fresh()->is_read);
        $this->assertFalse((bool) $fromOwner->fresh()->is_read);
    }

    public function test_deleted_conversation_participant_cannot_send_new_message(): void
    {
        [$owner, $subscriberUser, $conversation] = $this->makeConversation();

        $conversation->update(['user1_deleted_at' => null]);
        if ($conversation->user1_id === $subscriberUser->id) {
            $conversation->update(['user1_deleted_at' => now()]);
        } else {
            $conversation->update(['user2_deleted_at' => now()]);
        }

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/conversations/{$conversation->id}/messages", [
                'message_text' => 'محاولة بعد الحذف',
            ])
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_messages(): void
    {
        [,, $conversation] = $this->makeConversation();

        $this->getJson("/api/v1/conversations/{$conversation->id}/messages")->assertStatus(401);
    }
}
