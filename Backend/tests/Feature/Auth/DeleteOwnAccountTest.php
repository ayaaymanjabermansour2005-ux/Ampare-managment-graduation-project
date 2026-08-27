<?php

namespace Tests\Feature\Auth;

use App\Enums\Role as RoleEnum;
use App\Enums\SubscriptionStatus;
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

class DeleteOwnAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_subscriber_can_delete_own_account_with_correct_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password-123')]);
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/auth/account', ['password' => 'correct-password-123']);

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_deleting_account_with_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password-123')]);
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/auth/account', ['password' => 'wrong-password']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_delete_own_account_via_self_service(): void
    {
        $admin = User::factory()->create(['password' => bcrypt('correct-password-123')]);
        $admin->assignRole(RoleEnum::ADMIN->value);

        $response = $this->actingAs($admin)
            ->deleteJson('/api/v1/auth/account', ['password' => 'correct-password-123']);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_subscriber_with_active_subscription_cannot_delete_account(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $user = User::factory()->create(['password' => bcrypt('correct-password-123')]);
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $user->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/auth/account', ['password' => 'correct-password-123']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_password_is_required(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        $this->actingAs($user)
            ->deleteJson('/api/v1/auth/account', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_unauthenticated_user_cannot_delete_account(): void
    {
        $this->deleteJson('/api/v1/auth/account', ['password' => 'anything'])
            ->assertStatus(401);
    }
}
