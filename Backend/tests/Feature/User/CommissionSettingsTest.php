<?php

namespace Tests\Feature\User;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * ARCH-002: PATCH users/{user}/commission-settings had zero test coverage
 * before or after switching UpdateOwnerCommissionSettingsAction from a
 * global auth()->user() read to an explicit $actor parameter. These tests
 * close that gap.
 */
class CommissionSettingsTest extends TestCase
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

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }

    private function makeSubscriber(): User
    {
        $subscriber = User::factory()->create();
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        return $subscriber;
    }

    public function test_admin_can_set_fixed_commission_rate_for_owner(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$owner->id}/commission-settings", [
                'commission_mode' => 'fixed',
                'commission_rate' => 12.5,
            ])
            ->assertOk()
            ->assertJsonPath('data.commission_settings.mode', 'fixed');

        $owner->refresh();
        $this->assertSame('fixed', $owner->commission_mode->value);
        $this->assertEquals(12.5, $owner->commission_rate);
    }

    public function test_admin_can_switch_owner_to_tiered_commission_and_rate_is_cleared(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $owner->forceFill(['commission_mode' => 'fixed', 'commission_rate' => 10])->save();

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$owner->id}/commission-settings", [
                'commission_mode' => 'tiered',
            ])
            ->assertOk()
            ->assertJsonPath('data.commission_settings.mode', 'tiered');

        $owner->refresh();
        $this->assertSame('tiered', $owner->commission_mode->value);
        $this->assertNull($owner->commission_rate);
    }

    public function test_fixed_mode_without_rate_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$owner->id}/commission-settings", [
                'commission_mode' => 'fixed',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('commission_rate');
    }

    public function test_rate_above_100_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$owner->id}/commission-settings", [
                'commission_mode' => 'fixed',
                'commission_rate' => 150,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('commission_rate');
    }

    public function test_target_user_must_be_an_owner(): void
    {
        $admin = $this->makeAdmin();
        $subscriber = $this->makeSubscriber();

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$subscriber->id}/commission-settings", [
                'commission_mode' => 'fixed',
                'commission_rate' => 10,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('owner');
    }

    public function test_non_admin_owner_cannot_update_commission_settings(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$otherOwner->id}/commission-settings", [
                'commission_mode' => 'fixed',
                'commission_rate' => 10,
            ])
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_update_commission_settings(): void
    {
        $subscriber = $this->makeSubscriber();
        $owner = $this->makeOwner();

        $this->actingAs($subscriber)
            ->patchJson("/api/v1/users/{$owner->id}/commission-settings", [
                'commission_mode' => 'fixed',
                'commission_rate' => 10,
            ])
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_commission_settings(): void
    {
        $owner = $this->makeOwner();

        $this->patchJson("/api/v1/users/{$owner->id}/commission-settings", [
            'commission_mode' => 'fixed',
            'commission_rate' => 10,
        ])->assertStatus(401);
    }

    public function test_update_is_activity_logged_with_the_acting_admin(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$owner->id}/commission-settings", [
                'commission_mode' => 'fixed',
                'commission_rate' => 20,
            ])
            ->assertOk();

        $activity = Activity::where('description', 'owner_commission_settings_updated')
            ->where('subject_id', $owner->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($admin->id, $activity->causer_id);
        $this->assertSame('fixed', $activity->properties['mode']);
        $this->assertEquals(20, $activity->properties['rate']);
    }
}
