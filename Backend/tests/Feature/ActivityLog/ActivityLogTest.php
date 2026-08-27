<?php

namespace Tests\Feature\ActivityLog;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityLogTest extends TestCase
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

    private function logActivity(User $causer, $subject, string $description = 'حدث تجريبي'): void
    {
        activity()
            ->causedBy($causer)
            ->performedOn($subject)
            ->log($description);
    }

    public function test_admin_can_view_activity_logs(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $this->logActivity($owner, $generator);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/activity-logs');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.data'));
    }

    public function test_non_admin_cannot_view_activity_logs(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson('/api/v1/activity-logs')
            ->assertStatus(403);
    }

    public function test_filter_by_subject_type(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $this->logActivity($owner, $generator, 'حدث على مولد');

        $otherOwner = $this->makeOwner();
        $this->logActivity($otherOwner, $otherOwner, 'حدث على مستخدم');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/activity-logs?subject_type=generator');

        $response->assertOk();
        $descriptions = collect($response->json('data.data'))->pluck('description');
        $this->assertTrue($descriptions->contains('حدث على مولد'));
        $this->assertFalse($descriptions->contains('حدث على مستخدم'));
    }

    public function test_filter_by_causer_id(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->logActivity($owner, $generator, 'فعله الأونر الأول');
        $this->logActivity($otherOwner, $generator, 'فعله الأونر التاني');

        $response = $this->actingAs($admin)
            ->getJson("/api/v1/activity-logs?causer_id={$owner->id}");

        $response->assertOk();
        $descriptions = collect($response->json('data.data'))->pluck('description');
        $this->assertTrue($descriptions->contains('فعله الأونر الأول'));
        $this->assertFalse($descriptions->contains('فعله الأونر التاني'));
    }

    public function test_filter_by_date_range_excludes_out_of_range_logs(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->logActivity($owner, $generator, 'حدث قديم جدًا');
        DB::table('activity_log')
            ->where('description', 'حدث قديم جدًا')
            ->update(['created_at' => now()->subYear()]);

        $this->logActivity($owner, $generator, 'حدث حديث');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/activity-logs?from='.now()->subDay()->toDateString());

        $response->assertOk();
        $descriptions = collect($response->json('data.data'))->pluck('description');
        $this->assertTrue($descriptions->contains('حدث حديث'));
        $this->assertFalse($descriptions->contains('حدث قديم جدًا'));
    }

    public function test_per_page_is_capped_at_100(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $this->logActivity($owner, $generator);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/activity-logs?per_page=500');

        $response->assertOk();
        $this->assertSame(100, $response->json('data.per_page'));
    }

    public function test_unauthenticated_user_cannot_access_activity_logs(): void
    {
        $this->getJson('/api/v1/activity-logs')->assertStatus(401);
    }
}
