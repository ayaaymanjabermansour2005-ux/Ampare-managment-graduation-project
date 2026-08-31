<?php

namespace Tests\Feature\Subscription;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateSubscriptionNotesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeSubscription(User $owner): Subscription
    {
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        return Subscription::factory()->create(['generator_id' => $generator->id]);
    }

    public function test_owner_can_update_notes_for_own_subscription(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $subscription = $this->makeSubscription($owner);

        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/v1/subscriptions/{$subscription->id}/notes", [
            'notes' => 'المشترك دايمًا بيتأخر بالدفع.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.notes', 'المشترك دايمًا بيتأخر بالدفع.');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'notes' => 'المشترك دايمًا بيتأخر بالدفع.',
        ]);
    }

    public function test_admin_can_update_notes_for_any_subscription(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $subscription = $this->makeSubscription($owner);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/subscriptions/{$subscription->id}/notes", ['notes' => 'ملاحظة إدارية'])
            ->assertStatus(200)
            ->assertJsonPath('data.notes', 'ملاحظة إدارية');
    }

    public function test_owner_cannot_update_notes_for_another_owners_subscription(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $subscription = $this->makeSubscription($owner);

        $otherOwner = User::factory()->create();
        $otherOwner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        Sanctum::actingAs($otherOwner);

        $this->patchJson("/api/v1/subscriptions/{$subscription->id}/notes", ['notes' => 'محاولة تعديل'])
            ->assertStatus(403);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'notes' => null,
        ]);
    }

    public function test_subscriber_cannot_update_subscription_notes(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $subscription = $this->makeSubscription($owner);

        $subscriber = $subscription->subscriberMeter->subscriber->user;
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        Sanctum::actingAs($subscriber);

        $this->patchJson("/api/v1/subscriptions/{$subscription->id}/notes", ['notes' => 'محاولة تعديل'])
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_subscription_notes(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $subscription = $this->makeSubscription($owner);

        $this->patchJson("/api/v1/subscriptions/{$subscription->id}/notes", ['notes' => 'محاولة تعديل'])
            ->assertStatus(401);
    }

    public function test_notes_can_be_cleared_with_null(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $subscription = $this->makeSubscription($owner);
        $subscription->update(['notes' => 'ملاحظة قديمة']);

        Sanctum::actingAs($owner);

        $this->patchJson("/api/v1/subscriptions/{$subscription->id}/notes", ['notes' => null])
            ->assertStatus(200)
            ->assertJsonPath('data.notes', null);
    }

    public function test_subscriber_does_not_see_notes_on_subscription_resource(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $subscription = $this->makeSubscription($owner);
        $subscription->update(['notes' => 'ملاحظة سرية']);

        $subscriber = $subscription->subscriberMeter->subscriber->user;
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        Sanctum::actingAs($subscriber);

        $response = $this->getJson("/api/v1/subscriptions/{$subscription->id}");

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('notes', $response->json('data'));
    }
}
