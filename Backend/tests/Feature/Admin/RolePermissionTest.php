<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
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

    public function test_admin_can_view_roles_and_permissions(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/roles-permissions');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.roles'));
        $this->assertNotEmpty($response->json('data.all_permissions'));
    }

    public function test_non_admin_cannot_view_roles_and_permissions(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/admin/roles-permissions')
            ->assertStatus(403);
    }

    public function test_guest_cannot_view_roles_and_permissions(): void
    {
        $this->getJson('/api/v1/admin/roles-permissions')->assertStatus(401);
    }

    public function test_admin_can_sync_permissions_for_non_admin_role(): void
    {
        $admin = $this->makeAdmin();
        $role = Role::where('name', RoleEnum::GENERATOR_OWNER->value)->firstOrFail();
        $permission = Permission::first();

        $response = $this->actingAs($admin)->patchJson(
            "/api/v1/admin/roles/{$role->id}/permissions",
            ['permissions' => [$permission->name]]
        );

        $response->assertOk();
        $this->assertTrue($role->fresh()->hasPermissionTo($permission->name));
        // FIX (تدقيق شامل — A8): يجب أن تُعاد نفس بنية {id,name,permissions}
        // المستخدَمة بـ index()، لا نسخة Eloquent خام.
        $response->assertJson([
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => [$permission->name],
            ],
        ]);
    }

    public function test_admin_cannot_sync_permissions_for_admin_role(): void
    {
        $admin = $this->makeAdmin();
        $adminRole = Role::where('name', RoleEnum::ADMIN->value)->firstOrFail();
        $permission = Permission::first();

        $this->actingAs($admin)
            ->patchJson(
                "/api/v1/admin/roles/{$adminRole->id}/permissions",
                ['permissions' => [$permission->name]]
            )
            ->assertStatus(403);
    }

    public function test_sync_validates_permissions_exist(): void
    {
        $admin = $this->makeAdmin();
        $role = Role::where('name', RoleEnum::GENERATOR_OWNER->value)->firstOrFail();

        $this->actingAs($admin)
            ->patchJson(
                "/api/v1/admin/roles/{$role->id}/permissions",
                ['permissions' => ['does-not-exist.permission']]
            )
            ->assertStatus(422);
    }

    public function test_non_admin_cannot_sync_permissions(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $role = Role::where('name', RoleEnum::SUBSCRIBER->value)->firstOrFail();
        $permission = Permission::first();

        $this->actingAs($owner)
            ->patchJson(
                "/api/v1/admin/roles/{$role->id}/permissions",
                ['permissions' => [$permission->name]]
            )
            ->assertStatus(403);
    }
}
