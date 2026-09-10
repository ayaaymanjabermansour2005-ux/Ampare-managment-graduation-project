<?php

namespace Tests\Feature\User;

use App\Enums\Role as RoleEnum;
use App\Exports\UsersExport;
use App\Models\Conversation;
use App\Models\Generator;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class UserTest extends TestCase
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

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        return $user;
    }

    public function test_admin_can_view_any_user(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $this->actingAs($admin)
            ->getJson("/api/v1/users/{$owner->id}")
            ->assertOk();
    }

    public function test_user_can_view_own_profile(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson("/api/v1/users/{$owner->id}")
            ->assertOk();
    }

    public function test_user_cannot_view_another_users_profile(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson("/api/v1/users/{$otherOwner->id}")
            ->assertStatus(403);
    }

    public function test_user_can_update_own_name_and_phone(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$owner->id}", [
                'name' => 'اسم محدّث',
                'phone' => '+970599123456',
            ])
            ->assertOk();

        $this->assertSame('اسم محدّث', $owner->fresh()->name);
    }

    public function test_user_cannot_update_own_status(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$owner->id}", ['status' => 'inactive'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_admin_can_update_any_users_status(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$owner->id}", ['status' => 'inactive'])
            ->assertOk();

        $this->assertSame('inactive', $owner->fresh()->status->value);
    }

    public function test_changing_email_resets_verification_status(): void
    {
        $owner = $this->makeOwner();
        $this->assertNotNull($owner->email_verified_at);

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$owner->id}", ['email' => 'new-email-'.$owner->id.'@example.com'])
            ->assertOk();

        $this->assertNull($owner->fresh()->email_verified_at);
    }

    public function test_updating_without_changing_email_keeps_verification_status(): void
    {
        $owner = $this->makeOwner();
        $this->assertNotNull($owner->email_verified_at);

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$owner->id}", ['name' => 'اسم فقط'])
            ->assertOk();

        $this->assertNotNull($owner->fresh()->email_verified_at);
    }

    public function test_email_must_be_unique(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$owner->id}", ['email' => $otherOwner->email])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_user_cannot_update_another_users_profile(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$otherOwner->id}", ['name' => 'محاولة'])
            ->assertStatus(403);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/users/{$owner->id}")
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $owner->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/users/{$admin->id}")
            ->assertStatus(403);
    }

    public function test_non_admin_cannot_delete_any_user(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();

        $this->actingAs($owner)
            ->deleteJson("/api/v1/users/{$otherOwner->id}")
            ->assertStatus(403);
    }

    public function test_non_admin_cannot_delete_own_account_either(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->deleteJson("/api/v1/users/{$owner->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_list_users_filtered_by_role(): void
    {
        $admin = $this->makeAdmin();
        $this->makeOwner();
        $this->makeOwner();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/users?role='.RoleEnum::GENERATOR_OWNER->value);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_admin_can_filter_owners_by_commission_rate_range(): void
    {
        $admin = $this->makeAdmin();
        $lowRate = $this->makeOwner();
        $highRate = $this->makeOwner();

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$lowRate->id}/commission-settings", ['commission_mode' => 'fixed', 'commission_rate' => 2])
            ->assertOk();
        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$highRate->id}/commission-settings", ['commission_mode' => 'fixed', 'commission_rate' => 9])
            ->assertOk();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/users?role='.RoleEnum::GENERATOR_OWNER->value.'&commission_rate_min=5&commission_rate_max=10');

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($highRate->id));
        $this->assertFalse($ids->contains($lowRate->id));
    }

    public function test_admin_can_filter_owners_by_generators_count_range(): void
    {
        $admin = $this->makeAdmin();
        $ownerWithGenerators = $this->makeOwner();
        Generator::factory()->count(3)->create(['owner_id' => $ownerWithGenerators->id]);
        $ownerWithoutGenerators = $this->makeOwner();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/users?role='.RoleEnum::GENERATOR_OWNER->value.'&generators_count_min=1');

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($ownerWithGenerators->id));
        $this->assertFalse($ids->contains($ownerWithoutGenerators->id));
    }

    public function test_admin_can_search_users_by_name(): void
    {
        $admin = $this->makeAdmin();
        User::factory()->create(['name' => 'محمد الفريد']);
        User::factory()->create(['name' => 'شخص آخر']);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/users?search=الفريد');

        $response->assertOk();
        $names = collect($response->json('data.data'))->pluck('name');
        $this->assertTrue($names->contains('محمد الفريد'));
        $this->assertFalse($names->contains('شخص آخر'));
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson('/api/v1/users')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_users(): void
    {
        $this->getJson('/api/v1/users')->assertStatus(401);
    }

    public function test_conversations_no_longer_throws_sql_error(): void
    {
        $user = $this->makeUser();

        $result = $user->conversations()->get();

        $this->assertCount(0, $result);
    }

    public function test_conversations_returns_conversations_where_user_is_participant(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $third = $this->makeUser();
        $unrelated1 = $this->makeUser();
        $unrelated2 = $this->makeUser();

        $asUser1 = Conversation::create(['user1_id' => $user->id, 'user2_id' => $other->id]);
        $asUser2 = Conversation::create(['user1_id' => $third->id, 'user2_id' => $user->id]);

        $notIncludingUser = Conversation::create(['user1_id' => $unrelated1->id, 'user2_id' => $unrelated2->id]);

        $ids = $user->conversations()->pluck('id');

        $this->assertCount(2, $ids);
        $this->assertTrue($ids->contains($asUser1->id));
        $this->assertTrue($ids->contains($asUser2->id));
        $this->assertFalse($ids->contains($notIncludingUser->id));
    }

    public function test_conversations_excludes_conversation_deleted_by_this_user_only(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $conversation = Conversation::create([
            'user1_id' => $user->id,
            'user2_id' => $other->id,
            'user1_deleted_at' => now(),
        ]);

        $this->assertCount(0, $user->conversations()->get());

        $this->assertCount(1, $other->conversations()->get());
        $this->assertTrue($other->conversations()->pluck('id')->contains($conversation->id));
    }

    public function test_admin_can_export_all_users(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $this->makeOwner();
        $this->makeUser();

        $expectedCount = User::count();

        $this->actingAs($admin)->get('/api/v1/users/export')->assertOk();

        Excel::assertDownloaded(
            'users-'.now()->format('Y-m-d').'.xlsx',
            fn (UsersExport $export) => $export->query()->count() === $expectedCount
        );
    }

    public function test_user_export_respects_role_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $this->makeOwner();
        $this->makeUser();

        $this->actingAs($admin)
            ->get('/api/v1/users/export?role=subscriber')
            ->assertOk();

        Excel::assertDownloaded(
            'users-'.now()->format('Y-m-d').'.xlsx',
            function (UsersExport $export) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->hasRole('subscriber');
            }
        );
    }

    public function test_non_admin_cannot_export_users(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson('/api/v1/users/export')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_export_users(): void
    {
        $this->getJson('/api/v1/users/export')->assertStatus(401);
    }

    // ==================== API-001: owners/subscribers stats & export (no prior test coverage) ====================

    public function test_admin_can_view_owners_stats(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/users/owners-stats')
            ->assertOk();
    }

    public function test_non_admin_cannot_view_owners_stats(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson('/api/v1/users/owners-stats')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_view_owners_stats(): void
    {
        $this->getJson('/api/v1/users/owners-stats')->assertStatus(401);
    }

    public function test_admin_can_export_owners(): void
    {
        Excel::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/users/owners-export')
            ->assertOk();
    }

    public function test_non_admin_cannot_export_owners(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson('/api/v1/users/owners-export')
            ->assertStatus(403);
    }

    public function test_admin_can_view_subscribers_stats(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/users/subscribers-stats')
            ->assertOk();
    }

    public function test_non_admin_cannot_view_subscribers_stats(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson('/api/v1/users/subscribers-stats')
            ->assertStatus(403);
    }

    /**
     * FIX (تدقيق شامل — C1): outstanding_balance_ils كان يجمع القيمة الكاملة
     * للفاتورة حتى المدفوعة جزئيًا، بدل طرح المدفوع فعليًا — رقم مختلف عن
     * remaining_balance_ils الصحيح بصفحة الفواتير لنفس الفاتورة بالضبط.
     */
    public function test_outstanding_balance_subtracts_actual_payments_made(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription] = $this->makePartiallyPaidSubscriptionInvoice();

        $response = $this->actingAs($admin)->getJson('/api/v1/users?role=subscriber');

        $response->assertOk();
        $subscriberUserId = $subscription->subscriberMeter->subscriber->user_id;
        $item = collect($response->json('data.data'))->firstWhere('id', $subscriberUserId);

        // فاتورة final_amount_ils=200، دُفع منها 80 فعليًا → المتبقي الحقيقي 120.
        $this->assertEquals(120.0, $item['outstanding_balance_ils']);
    }

    public function test_subscribers_stats_top_debtors_subtracts_actual_payments_made(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription] = $this->makePartiallyPaidSubscriptionInvoice();

        $response = $this->actingAs($admin)->getJson('/api/v1/users/subscribers-stats');

        $response->assertOk();
        $subscriberUserId = $subscription->subscriberMeter->subscriber->user_id;
        $debtor = collect($response->json('data.top_debtors'))->firstWhere('id', $subscriberUserId);

        $this->assertNotNull($debtor);
        $this->assertEquals(120.0, $debtor['outstanding_balance_ils']);
    }

    /**
     * @return array{0: User, 1: \App\Models\Subscription} [subscriberUser, subscription]
     */
    private function makePartiallyPaidSubscriptionInvoice(): array
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = \App\Models\Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = \App\Models\SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $subscription = \App\Models\Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $invoice = \App\Models\Invoice::factory()->create([
            'subscription_id' => $subscription->id,
            'final_amount_ils' => 200,
            'status' => 'partially_paid',
        ]);

        \App\Models\Payment::factory()->paid()->create([
            'invoice_id' => $invoice->id,
            'amount_ils' => 80,
        ]);

        return [$subscriberUser, $subscription];
    }

    public function test_admin_can_export_subscribers(): void
    {
        Excel::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/users/subscribers-export')
            ->assertOk();
    }

    public function test_non_admin_cannot_export_subscribers(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->getJson('/api/v1/users/subscribers-export')
            ->assertStatus(403);
    }
}
