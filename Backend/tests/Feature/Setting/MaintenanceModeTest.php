<?php

namespace Tests\Feature\Setting;

use App\Enums\Role as RoleEnum;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_non_admin_is_blocked_during_maintenance(): void
    {
        Setting::set('maintenance_mode_enabled', '1');
        Setting::set('maintenance_message', 'صيانة مجدولة.');

        $subscriber = User::factory()->create();
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        $response = $this->actingAs($subscriber)->getJson('/api/v1/auth/me');

        $response->assertStatus(503);
        $this->assertSame('صيانة مجدولة.', $response->json('message'));
    }

    public function test_admin_bypasses_maintenance_mode(): void
    {
        Setting::set('maintenance_mode_enabled', '1');

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        $this->actingAs($admin)
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_requests_are_normal_when_maintenance_disabled(): void
    {
        $subscriber = User::factory()->create();
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        $this->actingAs($subscriber)
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }
}
