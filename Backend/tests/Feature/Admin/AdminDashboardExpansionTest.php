<?php

namespace Tests\Feature\Admin;

use App\Enums\ComplaintStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Complaint;
use App\Models\Generator;
use App\Models\Payment;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Support\Facades\Cache;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardExpansionTest extends TestCase
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

    public function test_admin_can_view_invoice_status_breakdown(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/invoice-status-breakdown');

        $response->assertOk();
        $this->assertArrayHasKey('paid', $response->json('data'));
        $this->assertArrayHasKey('pending', $response->json('data'));
        $this->assertArrayHasKey('overdue', $response->json('data'));
    }

    public function test_admin_can_view_realtime_alerts(): void
    {
        $admin = $this->makeAdmin();
        Complaint::create([
            'complainable_type' => User::class,
            'complainable_id' => $admin->id,
            'submitted_by' => $admin->id,
            'subject' => 'شكوى تجريبية',
            'description' => 'وصف.',
            'status' => ComplaintStatus::Pending->value,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/alerts');

        $response->assertOk();
        $this->assertIsArray($response->json('data'));
    }

    public function test_admin_can_view_payments_financial_summary(): void
    {
        $admin = $this->makeAdmin();

        Payment::factory()->paid()->create([
            'amount_ils' => 100,
            'paid_at' => now(),
        ]);
        Payment::factory()->paid()->create([
            'amount_ils' => 500,
            'paid_at' => now()->subMonths(2),
        ]);
        Payment::factory()->create([
            'status' => 'pending',
            'paid_at' => null,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/payments-financial-summary');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(100.0, $data['today_total_ils']);
        $this->assertEquals(100.0, $data['month_total_ils']);
        $this->assertSame(1, $data['month_transactions_count']);
        $this->assertSame(1, $data['pending_transactions_count']);
    }

    public function test_non_admin_cannot_view_payments_financial_summary(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/admin/dashboard/payments-financial-summary')
            ->assertStatus(403);
    }

    public function test_non_admin_cannot_view_dashboard_alerts(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/admin/dashboard/alerts')
            ->assertStatus(403);
    }

    public function test_generators_list_supports_search(): void
    {
        $admin = $this->makeAdmin();
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        Generator::factory()->create(['owner_id' => $owner->id, 'name' => 'مولد النور الخاص']);
        Generator::factory()->create(['owner_id' => $owner->id, 'name' => 'مولد الأمل']);

        $response = $this->actingAs($admin)->getJson('/api/v1/generators?search='.urlencode('النور'));

        $response->assertOk();
        $items = $response->json('data.data');
        $this->assertCount(1, $items);
        $this->assertSame('مولد النور الخاص', $items[0]['name']);
    }

    /**
     * FIX (تدقيق شامل — A5): clearCache() كانت تستخدم مفاتيح لا تطابق
     * المفاتيح الفعلية المحفوظة (stats بدل stats_v2 مثلًا) فتفشل بصمت.
     */
    public function test_clear_cache_forgets_every_key_actually_used_by_this_service(): void
    {
        $service = app(AdminDashboardService::class);

        $service->stats();
        $service->generatorsMapPoints();
        $service->revenueVsOutstandingDistribution();

        $this->assertTrue(Cache::has('admin.dashboard.stats_v2'));
        $this->assertTrue(Cache::has('admin.dashboard.generators_map_v2'));
        $this->assertTrue(Cache::has('admin.dashboard.revenue_vs_outstanding.p6'));

        $service->clearCache();

        $this->assertFalse(Cache::has('admin.dashboard.stats_v2'));
        $this->assertFalse(Cache::has('admin.dashboard.generators_map_v2'));
        $this->assertFalse(Cache::has('admin.dashboard.revenue_vs_outstanding.p6'));
    }
}
