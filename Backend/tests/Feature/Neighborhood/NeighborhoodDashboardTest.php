<?php

namespace Tests\Feature\Neighborhood;

use App\Enums\Role as RoleEnum;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Location;
use App\Models\Neighborhood;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeighborhoodDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_non_admin_cannot_view_neighborhood_dashboard(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/neighborhoods/dashboard')
            ->assertStatus(403);
    }

    public function test_admin_can_view_neighborhood_dashboard_summary(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        $neighborhood = Neighborhood::create(['name' => 'حي الاختبار']);
        $location = Location::factory()->create(['neighborhood_id' => $neighborhood->id]);
        $generator = Generator::factory()->create(['location_id' => $location->id, 'status' => 'active']);
        Subscription::factory()->create(['generator_id' => $generator->id, 'status' => 'active']);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/neighborhoods/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    ['neighborhood_id', 'neighborhood_name', 'generators_count', 'active_generators_count', 'active_subscriptions_count', 'open_faults_count'],
                ],
                'errors',
            ]);

        $row = collect($response->json('data'))->firstWhere('neighborhood_id', $neighborhood->id);

        $this->assertSame(1, $row['generators_count']);
        $this->assertSame(1, $row['active_generators_count']);
        $this->assertSame(1, $row['active_subscriptions_count']);
    }

    public function test_open_faults_count_excludes_resolved_rejected_and_closed_faults(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        $neighborhood = Neighborhood::create(['name' => 'حي الأعطال']);
        $location = Location::factory()->create(['neighborhood_id' => $neighborhood->id]);
        $generator = Generator::factory()->create(['location_id' => $location->id]);

        Fault::factory()->create(['generator_id' => $generator->id, 'status' => 'pending_verification']);
        Fault::factory()->create(['generator_id' => $generator->id, 'status' => 'verified']);
        Fault::factory()->create(['generator_id' => $generator->id, 'status' => 'in_repair']);
        Fault::factory()->create(['generator_id' => $generator->id, 'status' => 'resolved']);
        Fault::factory()->create(['generator_id' => $generator->id, 'status' => 'rejected']);
        Fault::factory()->create(['generator_id' => $generator->id, 'status' => 'closed']);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/neighborhoods/dashboard')
            ->assertOk();

        $row = collect($response->json('data'))->firstWhere('neighborhood_id', $neighborhood->id);

        $this->assertSame(3, $row['open_faults_count']);
    }
}
