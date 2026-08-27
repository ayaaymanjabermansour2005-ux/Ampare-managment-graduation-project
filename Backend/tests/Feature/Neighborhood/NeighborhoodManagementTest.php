<?php

namespace Tests\Feature\Neighborhood;

use App\Enums\Role as RoleEnum;
use App\Models\Neighborhood;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeighborhoodManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_admin_can_create_neighborhood(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/neighborhoods', ['name' => 'حي جديد'])
            ->assertStatus(201);

        $this->assertDatabaseHas('neighborhoods', ['name' => 'حي جديد']);
    }

    public function test_non_admin_cannot_create_neighborhood(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->postJson('/api/v1/admin/neighborhoods', ['name' => 'حي جديد'])
            ->assertStatus(403);
    }

    public function test_duplicate_neighborhood_name_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);
        Neighborhood::create(['name' => 'حي موجود']);

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/neighborhoods', ['name' => 'حي موجود'])
            ->assertStatus(422);
    }

    public function test_admin_can_update_neighborhood(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);
        $neighborhood = Neighborhood::create(['name' => 'اسم قديم']);

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/neighborhoods/{$neighborhood->id}", ['name' => 'اسم جديد'])
            ->assertOk();

        $this->assertSame('اسم جديد', $neighborhood->fresh()->name);
    }

    public function test_admin_can_delete_unused_neighborhood(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);
        $neighborhood = Neighborhood::create(['name' => 'حي فاضي']);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/neighborhoods/{$neighborhood->id}")
            ->assertOk();

        $this->assertDatabaseMissing('neighborhoods', ['id' => $neighborhood->id]);
    }

    public function test_admin_cannot_delete_neighborhood_with_subscribers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);
        $neighborhood = Neighborhood::create(['name' => 'حي فيه ناس']);

        $subscriberUser = User::factory()->create();
        Subscriber::factory()->create([
            'user_id' => $subscriberUser->id,
            'neighborhood_id' => $neighborhood->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/neighborhoods/{$neighborhood->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('neighborhoods', ['id' => $neighborhood->id]);
    }
}
