<?php

namespace Tests\Feature\Generator;

use App\Enums\Role as RoleEnum;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Technician;
use App\Models\TechnicianTask;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratorQuickScanTest extends TestCase
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

    public function test_linked_technician_can_quick_scan_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
        ]);
        $technician->generators()->attach($generator->id);

        Fault::create([
            'generator_id' => $generator->id,
            'source' => 'manual',
            'title' => 'عطل تجريبي',
            'description' => 'وصف.',
            'priority' => 'medium',
            'status' => 'verified',
            'reported_at' => now(),
        ]);

        TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'technician_id' => $technician->id,
            'requested_by' => $owner->id,
            'type' => 'general_maintenance',
            'status' => 'assigned',
            'assigned_by' => $owner->id,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($technicianUser)
            ->getJson("/api/v1/generators/{$generator->id}/quick-scan");

        $response->assertOk();
        $this->assertSame($generator->id, $response->json('data.generator.id'));
        $this->assertCount(1, $response->json('data.open_faults'));
        $this->assertCount(1, $response->json('data.recent_technician_tasks'));
    }

    public function test_unlinked_technician_cannot_quick_scan_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
        ]);
        // بدون attach

        $this->actingAs($technicianUser)
            ->getJson("/api/v1/generators/{$generator->id}/quick-scan")
            ->assertStatus(403);
    }

    public function test_owner_can_quick_scan_own_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        $this->actingAs($owner)
            ->getJson("/api/v1/generators/{$generator->id}/quick-scan")
            ->assertOk();
    }
}
