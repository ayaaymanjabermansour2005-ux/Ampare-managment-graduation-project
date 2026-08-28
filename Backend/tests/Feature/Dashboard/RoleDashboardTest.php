<?php

namespace Tests\Feature\Dashboard;

use App\Enums\Role as RoleEnum;
use App\Models\Subscriber;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API-001: RoleDashboardController's three endpoints had zero test coverage
 * before this file (confirmed via grep). Also covers TechnicianDashboardService
 * (new — previously missing entirely, causing a fatal error for any
 * technician who hit /technician/dashboard/stats).
 */
class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }

    private function makeSubscriberUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function makeTechnicianUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::TECHNICIAN->value);
        Technician::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    // ==================== owner/dashboard/stats ====================

    public function test_owner_can_view_own_dashboard_stats(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)->getJson('/api/v1/owner/dashboard/stats');

        $response->assertOk();
        $this->assertArrayHasKey('generators_count', $response->json('data'));
    }

    public function test_subscriber_cannot_view_owner_dashboard_stats(): void
    {
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->getJson('/api/v1/owner/dashboard/stats')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_view_owner_dashboard_stats(): void
    {
        $this->getJson('/api/v1/owner/dashboard/stats')->assertStatus(401);
    }

    // ==================== subscriber/dashboard/stats ====================

    public function test_subscriber_can_view_own_dashboard_stats(): void
    {
        $subscriberUser = $this->makeSubscriberUser();

        $response = $this->actingAs($subscriberUser)->getJson('/api/v1/subscriber/dashboard/stats');

        $response->assertOk();
        $this->assertArrayHasKey('active_subscriptions_count', $response->json('data'));
    }

    public function test_owner_cannot_view_subscriber_dashboard_stats(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson('/api/v1/subscriber/dashboard/stats')
            ->assertStatus(403);
    }

    // ==================== technician/dashboard/stats ====================

    public function test_technician_can_view_own_dashboard_stats(): void
    {
        $technicianUser = $this->makeTechnicianUser();

        $response = $this->actingAs($technicianUser)->getJson('/api/v1/technician/dashboard/stats');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertSame(0, $data['active_tasks_count']);
        $this->assertNull($data['average_rating']);
        $this->assertSame(0, $data['ratings_count']);
    }

    public function test_owner_cannot_view_technician_dashboard_stats(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson('/api/v1/technician/dashboard/stats')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_view_technician_dashboard_stats(): void
    {
        $this->getJson('/api/v1/technician/dashboard/stats')->assertStatus(401);
    }
}
