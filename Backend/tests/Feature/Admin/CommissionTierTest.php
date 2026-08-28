<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\CommissionTier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionTierTest extends TestCase
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

    public function test_admin_can_list_commission_tiers(): void
    {
        $admin = $this->makeAdmin();
        CommissionTier::create([
            'min_generators_count' => 0,
            'max_generators_count' => 5,
            'commission_rate' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/v1/commission-tiers');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_non_admin_cannot_list_commission_tiers(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/commission-tiers')
            ->assertStatus(403);
    }

    public function test_guest_cannot_access_commission_tiers(): void
    {
        $this->getJson('/api/v1/commission-tiers')->assertStatus(401);
    }

    public function test_admin_can_create_commission_tier(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->postJson('/api/v1/commission-tiers', [
            'min_generators_count' => 0,
            'max_generators_count' => 5,
            'commission_rate' => 10,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('commission_tiers', ['min_generators_count' => 0, 'max_generators_count' => 5]);
    }

    public function test_create_rejects_overlapping_range(): void
    {
        $admin = $this->makeAdmin();
        CommissionTier::create([
            'min_generators_count' => 0,
            'max_generators_count' => 5,
            'commission_rate' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/v1/commission-tiers', [
            'min_generators_count' => 3,
            'max_generators_count' => 8,
            'commission_rate' => 12,
            'is_active' => true,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['min_generators_count']);
    }

    // ==================== DB-004: further overlap-validation coverage (already-working behavior) ====================

    public function test_update_rejects_overlapping_range_with_another_tier(): void
    {
        $admin = $this->makeAdmin();
        CommissionTier::create([
            'min_generators_count' => 0,
            'max_generators_count' => 5,
            'commission_rate' => 10,
            'is_active' => true,
        ]);
        $other = CommissionTier::create([
            'min_generators_count' => 20,
            'max_generators_count' => 30,
            'commission_rate' => 12,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/v1/commission-tiers/{$other->id}", [
            'min_generators_count' => 3,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['min_generators_count']);
        $this->assertDatabaseHas('commission_tiers', ['id' => $other->id, 'min_generators_count' => 20]);
    }

    public function test_update_does_not_reject_overlap_with_itself(): void
    {
        $admin = $this->makeAdmin();
        $tier = CommissionTier::create([
            'min_generators_count' => 0,
            'max_generators_count' => 5,
            'commission_rate' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/v1/commission-tiers/{$tier->id}", [
            'commission_rate' => 11,
        ]);

        $response->assertOk();
    }

    public function test_create_allows_adjacent_non_overlapping_ranges(): void
    {
        $admin = $this->makeAdmin();
        CommissionTier::create([
            'min_generators_count' => 0,
            'max_generators_count' => 5,
            'commission_rate' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/v1/commission-tiers', [
            'min_generators_count' => 6,
            'max_generators_count' => 10,
            'commission_rate' => 12,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
    }

    public function test_create_rejects_overlap_with_an_open_ended_tier(): void
    {
        $admin = $this->makeAdmin();
        CommissionTier::create([
            'min_generators_count' => 10,
            'max_generators_count' => null,
            'commission_rate' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/v1/commission-tiers', [
            'min_generators_count' => 8,
            'max_generators_count' => 12,
            'commission_rate' => 12,
            'is_active' => true,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['min_generators_count']);
    }

    public function test_inactive_tier_does_not_block_an_overlapping_new_active_tier(): void
    {
        $admin = $this->makeAdmin();
        CommissionTier::create([
            'min_generators_count' => 0,
            'max_generators_count' => 5,
            'commission_rate' => 10,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/v1/commission-tiers', [
            'min_generators_count' => 3,
            'max_generators_count' => 8,
            'commission_rate' => 12,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
    }

    public function test_admin_can_update_commission_tier(): void
    {
        $admin = $this->makeAdmin();
        $tier = CommissionTier::create([
            'min_generators_count' => 0,
            'max_generators_count' => 5,
            'commission_rate' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/v1/commission-tiers/{$tier->id}", [
            'commission_rate' => 15,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('commission_tiers', ['id' => $tier->id, 'commission_rate' => 15]);
    }

    public function test_admin_can_delete_commission_tier(): void
    {
        $admin = $this->makeAdmin();
        $tier = CommissionTier::create([
            'min_generators_count' => 0,
            'max_generators_count' => 5,
            'commission_rate' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/commission-tiers/{$tier->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('commission_tiers', ['id' => $tier->id]);
    }
}
