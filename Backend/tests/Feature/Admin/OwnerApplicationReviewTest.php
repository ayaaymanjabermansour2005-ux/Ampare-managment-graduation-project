<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Exports\OwnerApplicationsExport;
use App\Models\OwnerApplication;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class OwnerApplicationReviewTest extends TestCase
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

    private function makeApplication(array $overrides = []): OwnerApplication
    {
        $status = $overrides['status'] ?? 'pending';
        unset($overrides['status']);

        $application = OwnerApplication::create(array_merge([
            'name' => 'أحمد علي',
            'email' => 'app-'.uniqid().'@example.com',
            'phone' => '+970599123456',
            'password' => Hash::make('StrongPass123!'),
            'notes' => null,
            'generator_name' => 'مولد تجريبي',
            'generator_price_per_kw' => 2.5,
            'generator_currency' => 'ILS',
            'generator_capacity_kw' => 50,
            'generator_city' => 'غزة',
            'generator_address' => 'شارع الرئيسي',
        ], $overrides));

        if ($status !== 'pending') {
            $application->forceFill(['status' => $status])->save();
        }

        return $application;
    }

    public function test_non_admin_cannot_view_owner_applications(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/admin/owner-applications')
            ->assertStatus(403);
    }

    public function test_non_admin_cannot_approve_or_reject_applications(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $application = $this->makeApplication();

        $this->actingAs($owner)
            ->postJson("/api/v1/admin/owner-applications/{$application->id}/approve")
            ->assertStatus(403);

        $this->actingAs($owner)
            ->postJson("/api/v1/admin/owner-applications/{$application->id}/reject")
            ->assertStatus(403);
    }

    public function test_admin_can_list_applications_with_status_counts(): void
    {
        $admin = $this->makeAdmin();
        $this->makeApplication(['status' => 'pending']);
        $this->makeApplication(['status' => 'approved']);
        $this->makeApplication(['status' => 'rejected']);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/owner-applications');

        $response->assertOk();
        $counts = $response->json('data.meta.status_counts');
        $this->assertSame(1, $counts['pending']);
        $this->assertSame(1, $counts['approved']);
        $this->assertSame(1, $counts['rejected']);
        $this->assertSame(3, $counts['all']);
    }

    public function test_status_filter_only_returns_matching_applications(): void
    {
        $admin = $this->makeAdmin();
        $this->makeApplication(['status' => 'pending', 'name' => 'طلب معلّق']);
        $this->makeApplication(['status' => 'approved', 'name' => 'طلب مقبول']);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/owner-applications?status=pending');

        $response->assertOk();
        $items = $response->json('data.data');
        $this->assertCount(1, $items);
        $this->assertSame('طلب معلّق', $items[0]['name']);
    }

    public function test_search_filters_by_name_email_and_phone(): void
    {
        $admin = $this->makeAdmin();
        $this->makeApplication(['name' => 'محمد الأسطل', 'email' => 'mohammad@example.com']);
        $this->makeApplication(['name' => 'خالد النجار', 'email' => 'khaled@example.com']);

        $byName = $this->actingAs($admin)->getJson('/api/v1/admin/owner-applications?search='.urlencode('الأسطل'));
        $byName->assertOk();
        $this->assertCount(1, $byName->json('data.data'));
        $this->assertSame('محمد الأسطل', $byName->json('data.data.0.name'));

        $byEmail = $this->actingAs($admin)->getJson('/api/v1/admin/owner-applications?search=khaled@example.com');
        $byEmail->assertOk();
        $this->assertCount(1, $byEmail->json('data.data'));
        $this->assertSame('خالد النجار', $byEmail->json('data.data.0.name'));
    }

    public function test_sort_newest_first_orders_by_created_at_descending(): void
    {
        $admin = $this->makeAdmin();
        $older = $this->makeApplication(['name' => 'الأقدم']);
        $older->forceFill(['created_at' => now()->subDays(5)])->save();
        $newer = $this->makeApplication(['name' => 'الأحدث']);
        $newer->forceFill(['created_at' => now()->subDay()])->save();

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/owner-applications?sort=created_desc');

        $response->assertOk();
        $names = collect($response->json('data.data'))->pluck('name')->all();
        $this->assertSame(['الأحدث', 'الأقدم'], $names);
    }

    public function test_sort_oldest_first_orders_by_created_at_ascending(): void
    {
        $admin = $this->makeAdmin();
        $older = $this->makeApplication(['name' => 'الأقدم']);
        $older->forceFill(['created_at' => now()->subDays(5)])->save();
        $newer = $this->makeApplication(['name' => 'الأحدث']);
        $newer->forceFill(['created_at' => now()->subDay()])->save();

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/owner-applications?sort=created_asc');

        $response->assertOk();
        $names = collect($response->json('data.data'))->pluck('name')->all();
        $this->assertSame(['الأقدم', 'الأحدث'], $names);
    }

    public function test_admin_can_approve_pending_application_and_it_creates_owner_and_generator(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $application = $this->makeApplication([
            'email' => 'new-owner@example.com',
            'generator_name' => 'مولد الحي الجديد',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/owner-applications/{$application->id}/approve");

        $response->assertOk();
        $application->refresh();
        $this->assertSame('approved', $application->status->value);
        $this->assertSame($admin->id, $application->reviewed_by);
        $this->assertNotNull($application->created_user_id);

        $owner = User::find($application->created_user_id);
        $this->assertNotNull($owner);
        $this->assertTrue($owner->hasRole(RoleEnum::GENERATOR_OWNER->value));

        $this->assertDatabaseHas('generators', [
            'name' => 'مولد الحي الجديد',
            'owner_id' => $owner->id,
        ]);
    }

    public function test_admin_can_reject_pending_application_with_reason(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $application = $this->makeApplication();

        $response = $this->actingAs($admin)->postJson(
            "/api/v1/admin/owner-applications/{$application->id}/reject",
            ['reason' => 'مستندات غير كافية']
        );

        $response->assertOk();
        $application->refresh();
        $this->assertSame('rejected', $application->status->value);
        $this->assertSame('مستندات غير كافية', $application->review_note);
        $this->assertNull($application->created_user_id);
    }

    public function test_already_reviewed_application_cannot_be_approved_again(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $application = $this->makeApplication(['status' => 'approved']);

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/owner-applications/{$application->id}/approve")
            ->assertStatus(403);
    }

    public function test_admin_can_update_internal_note(): void
    {
        $admin = $this->makeAdmin();
        $application = $this->makeApplication();

        $response = $this->actingAs($admin)->patchJson(
            "/api/v1/admin/owner-applications/{$application->id}/internal-note",
            ['internal_note' => 'تحقّقت من المستندات يدويًا.']
        );

        $response->assertOk();
        $this->assertSame('تحقّقت من المستندات يدويًا.', $application->fresh()->internal_note);
    }

    public function test_date_range_filter_only_returns_applications_within_range(): void
    {
        $admin = $this->makeAdmin();

        $inRange = $this->makeApplication(['name' => 'داخل النطاق']);
        $inRange->forceFill(['created_at' => now()->subDays(3)])->save();

        $outOfRange = $this->makeApplication(['name' => 'خارج النطاق']);
        $outOfRange->forceFill(['created_at' => now()->subDays(20)])->save();

        $response = $this->actingAs($admin)->getJson(
            '/api/v1/admin/owner-applications?from_date='.now()->subDays(5)->toDateString()
            .'&to_date='.now()->toDateString()
        );

        $response->assertOk();
        $names = collect($response->json('data.data'))->pluck('name')->all();
        $this->assertSame(['داخل النطاق'], $names);
    }

    public function test_to_date_before_from_date_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson(
            '/api/v1/admin/owner-applications?from_date='.now()->toDateString()
            .'&to_date='.now()->subDays(5)->toDateString()
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['to_date']);
    }

    public function test_duplicate_user_id_is_returned_when_email_matches_existing_user(): void
    {
        $admin = $this->makeAdmin();
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $application = $this->makeApplication(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/owner-applications');

        $response->assertOk();
        $item = collect($response->json('data.data'))->firstWhere('id', $application->id);
        $this->assertTrue($item['is_duplicate_email']);
        $this->assertSame($existingUser->id, $item['duplicate_user_id']);
    }

    public function test_admin_can_bulk_approve_pending_applications(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $first = $this->makeApplication(['email' => 'bulk1@example.com', 'phone' => '+970599111111', 'generator_name' => 'مولد 1']);
        $second = $this->makeApplication(['email' => 'bulk2@example.com', 'phone' => '+970599222222', 'generator_name' => 'مولد 2']);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/owner-applications/bulk-approve', [
            'application_ids' => [$first->id, $second->id],
        ]);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.approved'));
        $this->assertCount(0, $response->json('data.failed'));

        $this->assertSame('approved', $first->fresh()->status->value);
        $this->assertSame('approved', $second->fresh()->status->value);
        $this->assertDatabaseHas('generators', ['name' => 'مولد 1']);
        $this->assertDatabaseHas('generators', ['name' => 'مولد 2']);
    }

    public function test_bulk_approve_reports_partial_failure_for_already_reviewed_application(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $pending = $this->makeApplication(['email' => 'pending@example.com']);
        $alreadyApproved = $this->makeApplication(['email' => 'already@example.com', 'status' => 'approved']);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/owner-applications/bulk-approve', [
            'application_ids' => [$pending->id, $alreadyApproved->id],
        ]);

        $response->assertOk();
        $this->assertSame([$pending->id], $response->json('data.approved'));
        $this->assertArrayHasKey((string) $alreadyApproved->id, $response->json('data.failed'));
    }

    public function test_admin_can_bulk_reject_pending_applications_with_reason(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $first = $this->makeApplication(['email' => 'bulkreject1@example.com']);
        $second = $this->makeApplication(['email' => 'bulkreject2@example.com']);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/owner-applications/bulk-reject', [
            'application_ids' => [$first->id, $second->id],
            'reason' => 'مستندات غير مكتملة',
        ]);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.rejected'));
        $this->assertSame('rejected', $first->fresh()->status->value);
        $this->assertSame('مستندات غير مكتملة', $first->fresh()->review_note);
        $this->assertNull($first->fresh()->created_user_id);
    }

    public function test_non_admin_cannot_bulk_approve_or_reject(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $application = $this->makeApplication();

        $this->actingAs($owner)
            ->postJson('/api/v1/admin/owner-applications/bulk-approve', ['application_ids' => [$application->id]])
            ->assertStatus(403);

        $this->actingAs($owner)
            ->postJson('/api/v1/admin/owner-applications/bulk-reject', ['application_ids' => [$application->id]])
            ->assertStatus(403);
    }

    public function test_admin_can_export_owner_applications_matching_filters(): void
    {
        Excel::fake();
        $admin = $this->makeAdmin();
        $matching = $this->makeApplication(['name' => 'طلب مطابق', 'status' => 'pending']);
        $this->makeApplication(['name' => 'طلب آخر', 'status' => 'approved']);

        $this->actingAs($admin)
            ->get('/api/v1/admin/owner-applications/export?status=pending')
            ->assertOk();

        Excel::assertDownloaded(
            'owner-applications-'.now()->format('Y-m-d').'.xlsx',
            function (OwnerApplicationsExport $export) use ($matching) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->id === $matching->id;
            }
        );
    }

    public function test_review_history_is_queryable_via_activity_log_endpoint(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $application = $this->makeApplication();

        $this->actingAs($admin)->postJson("/api/v1/admin/owner-applications/{$application->id}/approve")->assertOk();

        $response = $this->actingAs($admin)->getJson(
            "/api/v1/activity-logs?subject_type=owner_application&subject_id={$application->id}"
        );

        $response->assertOk();
        $logs = $response->json('data.data');
        $this->assertNotEmpty($logs);
        $statuses = collect($logs)->pluck('changes.attributes.status')->filter()->all();
        $this->assertContains('approved', $statuses);
        foreach ($logs as $log) {
            $this->assertSame('OwnerApplication', $log['subject_type']);
            $this->assertSame($application->id, $log['subject_id']);
        }
    }
}
