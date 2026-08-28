<?php

namespace Tests\Feature\PlatformCommission;

use App\Enums\Role as RoleEnum;
use App\Exports\PlatformCommissionsExport;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\PlatformCommission;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PlatformCommissionTest extends TestCase
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

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    private function makeSubscriberUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * @return array{0: PlatformCommission, 1: Invoice}
     */
    private function makeCommission(User $owner, string $status = 'pending'): array
    {
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
        ]);

        $invoice = Invoice::factory()->create([
            'subscription_id' => $subscription->id,
        ]);

        $commission = PlatformCommission::factory()->create([
            'invoice_id' => $invoice->id,
            'owner_id' => $owner->id,
            'commission_rate' => 10,
            'commission_amount' => 50,
            'status' => $status,
        ]);

        return [$commission, $invoice];
    }

    public function test_owner_sees_only_own_commissions(): void
    {
        $owner = $this->makeOwner();
        [$ownCommission] = $this->makeCommission($owner);

        $otherOwner = $this->makeOwner();
        $this->makeCommission($otherOwner);

        $response = $this->actingAs($owner)
            ->getJson('/api/v1/platform-commissions');

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($ownCommission->id));
        $this->assertCount(1, $ids);
    }

    public function test_admin_sees_all_commissions(): void
    {
        $admin = $this->makeAdmin();
        $owner1 = $this->makeOwner();
        $owner2 = $this->makeOwner();
        $this->makeCommission($owner1);
        $this->makeCommission($owner2);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/platform-commissions');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_subscriber_cannot_list_commissions(): void
    {
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->getJson('/api/v1/platform-commissions')
            ->assertStatus(403);
    }

    public function test_admin_can_mark_earned_commission_as_paid(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$commission] = $this->makeCommission($owner, 'earned');

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/platform-commissions/{$commission->id}/status", ['status' => 'paid']);

        $response->assertOk();
        $fresh = $commission->fresh();
        $this->assertSame('paid', $fresh->status->value);
        $this->assertNotNull($fresh->paid_at);
    }

    public function test_owner_cannot_mark_own_commission_as_paid(): void
    {
        $owner = $this->makeOwner();
        [$commission] = $this->makeCommission($owner, 'earned');

        $this->actingAs($owner)
            ->patchJson("/api/v1/platform-commissions/{$commission->id}/status", ['status' => 'paid'])
            ->assertStatus(403);
    }

    public function test_cannot_mark_pending_commission_as_paid(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$commission] = $this->makeCommission($owner, 'pending');

        $this->actingAs($admin)
            ->patchJson("/api/v1/platform-commissions/{$commission->id}/status", ['status' => 'paid'])
            ->assertStatus(422);

        $this->assertSame('pending', $commission->fresh()->status->value);
    }

    public function test_cannot_send_invalid_status_value(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$commission] = $this->makeCommission($owner, 'earned');

        $this->actingAs($admin)
            ->patchJson("/api/v1/platform-commissions/{$commission->id}/status", ['status' => 'pending'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_owner_can_download_own_report(): void
    {
        $owner = $this->makeOwner();
        $this->makeCommission($owner, 'earned');

        $this->actingAs($owner)
            ->get('/api/v1/platform-commissions/report-pdf?' . http_build_query([
                'from' => now()->subMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk();
    }

    public function test_admin_must_specify_owner_id_for_report(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/api/v1/platform-commissions/report-pdf')
            ->assertStatus(422);
    }

    public function test_admin_can_download_report_for_specific_owner(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $this->makeCommission($owner, 'earned');

        $this->actingAs($admin)
            ->get('/api/v1/platform-commissions/report-pdf?' . http_build_query(['owner_id' => $owner->id]))
            ->assertOk();
    }

    public function test_owner_requesting_foreign_owner_id_gets_own_report_instead(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $this->makeCommission($otherOwner, 'earned');

        $this->actingAs($owner)
            ->get('/api/v1/platform-commissions/report-pdf?' . http_build_query(['owner_id' => $otherOwner->id]))
            ->assertOk();
    }

    public function test_admin_can_export_commissions(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$ownerCommission] = $this->makeCommission($owner);

        $otherOwner = $this->makeOwner();
        $this->makeCommission($otherOwner);

        $this->actingAs($admin)
            ->get('/api/v1/platform-commissions/export')
            ->assertOk();

        Excel::assertDownloaded(
            'commissions-' . now()->format('Y-m-d') . '.xlsx',
            fn(PlatformCommissionsExport $export) => $export->query()->count() === 2
        );
    }

    public function test_owner_export_only_includes_own_commissions(): void
    {
        Excel::fake();

        $owner = $this->makeOwner();
        [$ownCommission] = $this->makeCommission($owner);

        $otherOwner = $this->makeOwner();
        $this->makeCommission($otherOwner);

        $this->actingAs($owner)
            ->get('/api/v1/platform-commissions/export')
            ->assertOk();

        Excel::assertDownloaded(
            'commissions-' . now()->format('Y-m-d') . '.xlsx',
            function (PlatformCommissionsExport $export) use ($ownCommission) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->id === $ownCommission->id;
            }
        );
    }

    public function test_subscriber_cannot_export_commissions(): void
    {
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->get('/api/v1/platform-commissions/export')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_commissions(): void
    {
        $this->getJson('/api/v1/platform-commissions')->assertStatus(401);
    }

    // ==================== DB-003: PlatformCommission soft-delete consistency ====================

    public function test_platform_commission_supports_soft_deletes_like_its_sibling_financial_tables(): void
    {
        $owner = $this->makeOwner();
        [$commission] = $this->makeCommission($owner);

        $commission->delete();

        // Excluded from default (non-trashed) queries, exactly like
        // Invoice/Payment/TechnicianPayment already behave.
        $this->assertNull(PlatformCommission::find($commission->id));

        // But not actually gone — a real soft delete, not a hard delete;
        // the platform's own revenue ledger must remain auditable.
        $trashed = PlatformCommission::withTrashed()->find($commission->id);
        $this->assertNotNull($trashed);
        $this->assertNotNull($trashed->deleted_at);

        $this->assertDatabaseHas('platform_commissions', [
            'id' => $commission->id,
        ]);
    }

    public function test_owner_no_longer_sees_a_soft_deleted_commission_in_their_list(): void
    {
        $owner = $this->makeOwner();
        [$commission] = $this->makeCommission($owner);

        $commission->delete();

        $response = $this->actingAs($owner)->getJson('/api/v1/platform-commissions');

        $response->assertOk();
        $ids = collect($response->json('data.data') ?? $response->json('data'))->pluck('id');
        $this->assertNotContains($commission->id, $ids);
    }
}
