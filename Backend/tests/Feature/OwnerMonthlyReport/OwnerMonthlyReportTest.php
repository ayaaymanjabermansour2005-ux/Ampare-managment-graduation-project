<?php

namespace Tests\Feature\OwnerMonthlyReport;

use App\Enums\Role;
use App\Models\Generator;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OwnerMonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_owner_can_download_own_monthly_report(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(Role::GENERATOR_OWNER->value);
        Generator::factory()->create(['owner_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $response = $this->get('/api/v1/owner-monthly-report/download');

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_owner_cannot_download_another_owners_report(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(Role::GENERATOR_OWNER->value);

        $otherOwner = User::factory()->create();
        $otherOwner->assignRole(Role::GENERATOR_OWNER->value);
        Generator::factory()->create(['owner_id' => $otherOwner->id]);

        Sanctum::actingAs($owner);

        $this->get("/api/v1/owner-monthly-report/download?owner_id={$otherOwner->id}")
            ->assertStatus(403);
    }

    public function test_admin_must_specify_owner_id(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);

        Sanctum::actingAs($admin);

        $this->get('/api/v1/owner-monthly-report/download')
            ->assertStatus(422);
    }

    public function test_guest_cannot_download_owner_monthly_report(): void
    {
        $this->get('/api/v1/owner-monthly-report/download')
            ->assertStatus(401);
    }

    /**
     * API-001: route now carries `role:admin|generator_owner` middleware. Before
     * this, any non-admin authenticated user (subscriber/technician included)
     * reached the controller and got a self-scoped, effectively-empty report
     * instead of a 403.
     */
    public function test_subscriber_cannot_access_owner_monthly_report(): void
    {
        $subscriber = User::factory()->create();
        $subscriber->assignRole(Role::SUBSCRIBER->value);

        Sanctum::actingAs($subscriber);

        $this->get('/api/v1/owner-monthly-report/download')
            ->assertStatus(403);
    }
}
