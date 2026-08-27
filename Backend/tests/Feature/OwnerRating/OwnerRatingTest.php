<?php

namespace Tests\Feature\OwnerRating;

use App\Enums\Role;
use App\Models\Generator;
use App\Models\OwnerRating;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OwnerRatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeActiveSubscription(): Subscription
    {
        $owner = User::factory()->create();
        $owner->assignRole(Role::GENERATOR_OWNER->value);

        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscription = Subscription::factory()->create([
            'generator_id' => $generator->id,
            'status' => 'active',
        ]);

        return $subscription->load('subscriberMeter.subscriber.user', 'generator');
    }

    public function test_subscriber_can_rate_owner_for_active_subscription(): void
    {
        $subscription = $this->makeActiveSubscription();
        $rater = $subscription->subscriberMeter->subscriber->user;
        $rater->assignRole(Role::SUBSCRIBER->value);

        Sanctum::actingAs($rater);

        $response = $this->postJson("/api/v1/subscriptions/{$subscription->id}/owner-rating", [
            'rating' => 5,
            'comment' => 'خدمة ممتازة',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('owner_ratings', [
            'subscription_id' => $subscription->id,
            'rated_by' => $rater->id,
            'owner_id' => $subscription->generator->owner_id,
        ]);
    }

    public function test_subscriber_cannot_rate_owner_twice_for_same_subscription(): void
    {
        $subscription = $this->makeActiveSubscription();
        $rater = $subscription->subscriberMeter->subscriber->user;
        $rater->assignRole(Role::SUBSCRIBER->value);

        OwnerRating::factory()->create([
            'subscription_id' => $subscription->id,
            'owner_id' => $subscription->generator->owner_id,
            'rated_by' => $rater->id,
        ]);

        Sanctum::actingAs($rater);

        $response = $this->postJson("/api/v1/subscriptions/{$subscription->id}/owner-rating", [
            'rating' => 3,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(1, OwnerRating::where('subscription_id', $subscription->id)->count());
    }

    public function test_subscriber_cannot_rate_owner_for_pending_subscription(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(Role::GENERATOR_OWNER->value);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscription = Subscription::factory()->create([
            'generator_id' => $generator->id,
            'status' => 'pending',
        ])->load('subscriberMeter.subscriber.user');

        $rater = $subscription->subscriberMeter->subscriber->user;
        $rater->assignRole(Role::SUBSCRIBER->value);

        Sanctum::actingAs($rater);

        $this->postJson("/api/v1/subscriptions/{$subscription->id}/owner-rating", ['rating' => 5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscription');
    }

    public function test_unauthenticated_user_cannot_rate_owner(): void
    {
        $subscription = $this->makeActiveSubscription();

        $this->postJson("/api/v1/subscriptions/{$subscription->id}/owner-rating", ['rating' => 5])
            ->assertStatus(401);
    }

    public function test_unrelated_subscriber_cannot_rate_owner(): void
    {
        $subscription = $this->makeActiveSubscription();

        $stranger = User::factory()->create();
        $stranger->assignRole(Role::SUBSCRIBER->value);

        Sanctum::actingAs($stranger);

        $this->postJson("/api/v1/subscriptions/{$subscription->id}/owner-rating", ['rating' => 5])
            ->assertStatus(403);
    }
}
