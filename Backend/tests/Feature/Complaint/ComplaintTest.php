<?php

namespace Tests\Feature\Complaint;

use App\Enums\Role as RoleEnum;
use App\Exports\ComplaintsExport;
use App\Models\Complaint;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\Technician;
use App\Models\TechnicianTask;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ComplaintTest extends TestCase
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

    private function makeTechnicianUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::TECHNICIAN->value);

        return $user;
    }

    /**
     * @return array{0: Subscriber, 1: User, 2: Subscription}
     */
    private function makeConnectedSubscriber(Generator $generator): array
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $user->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
        ]);

        return [$subscriber, $user, $subscription];
    }

    public function test_subscriber_can_submit_general_complaint_without_complainable(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $response = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/complaints', [
                'subject' => 'شكوى عامة',
                'description' => 'تفاصيل الشكوى العامة.',
            ]);

        $response->assertStatus(201);
        $this->assertSame('pending', $response->json('data.status'));
        $this->assertDatabaseHas('complaints', ['subject' => 'شكوى عامة', 'submitted_by' => $subscriberUser->id]);
    }

    public function test_sla_due_at_is_computed_from_priority(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $response = $this->actingAs($subscriberUser)
            ->postJson('/api/v1/complaints', [
                'subject' => 'انقطاع كامل عن الكهرباء',
                'description' => 'تفاصيل.',
                'priority' => 'urgent',
            ]);

        $response->assertStatus(201);
        $complaint = Complaint::findOrFail($response->json('data.id'));

        $this->assertNotNull($complaint->sla_due_at);
        $this->assertEqualsWithDelta(
            $complaint->created_at->addHours(4)->timestamp,
            $complaint->sla_due_at->timestamp,
            1
        );
    }

    public function test_owner_can_submit_complaint_about_own_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'generator',
                'complainable_id' => $generator->id,
                'subject' => 'مشكلة بالمولد',
                'description' => 'تفاصيل.',
            ]);

        $response->assertStatus(201);
        $this->assertSame('Generator', $response->json('data.complainable.type'));
    }

    public function test_subscriber_cannot_submit_complaint_about_unrelated_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $unrelatedSubscriberUser] = $this->makeConnectedSubscriber(
            Generator::factory()->create(['owner_id' => $this->makeOwner()->id])
        );

        $this->actingAs($unrelatedSubscriberUser)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'generator',
                'complainable_id' => $generator->id,
                'subject' => 'محاولة',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('complainable_id');
    }

    public function test_subscriber_can_submit_complaint_about_own_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser, $subscription] = $this->makeConnectedSubscriber($generator);

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'subscription',
                'complainable_id' => $subscription->id,
                'subject' => 'مشكلة بالاشتراك',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(201);
    }

    public function test_invalid_complainable_type_is_rejected(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'not_a_real_type',
                'complainable_id' => 1,
                'subject' => 'شكوى',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('complainable_type');
    }

    public function test_complainable_id_required_when_type_given(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'generator',
                'subject' => 'شكوى',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('complainable_id');
    }

    public function test_technician_can_submit_complaint(): void
    {
        // Technicians gained complaints.create as part of the Technician Portal's
        // "More" menu (Complaint entry) — see docs/technician-portal-audit.md.
        $technicianUser = $this->makeTechnicianUser();

        $this->actingAs($technicianUser)
            ->postJson('/api/v1/complaints', [
                'subject' => 'شكوى',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(201);
    }

    public function test_admin_can_submit_complaint_about_any_generator(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($admin)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'generator',
                'complainable_id' => $generator->id,
                'subject' => 'مراجعة إدارية',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(403);
    }

    /* ---------------------------------------------------------------
     | "user" complainable type — علاقة خدمة فعلية مطلوبة (تدقيق شامل — الجولة الرابعة)
     |--------------------------------------------------------------- */

    public function test_subscriber_can_submit_complaint_about_technician_who_serviced_their_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $technicianUser = $this->makeTechnicianUser();
        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
        ]);
        TechnicianTask::factory()->assigned()->create([
            'generator_id' => $generator->id,
            'technician_id' => $technician->id,
        ]);

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'user',
                'complainable_id' => $technicianUser->id,
                'subject' => 'شكوى عن تعامل الفني',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(201);
    }

    public function test_subscriber_cannot_submit_complaint_about_unrelated_user(): void
    {
        $owner = $this->makeOwner();
        [, $subscriberUser] = $this->makeConnectedSubscriber(Generator::factory()->create(['owner_id' => $owner->id]));
        $unrelatedUser = User::factory()->create();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'user',
                'complainable_id' => $unrelatedUser->id,
                'subject' => 'شكوى',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('complainable_id');
    }

    public function test_owner_can_submit_complaint_about_their_own_technician(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianUser();
        Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'user',
                'complainable_id' => $technicianUser->id,
                'subject' => 'شكوى عن فني',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(201);
    }

    public function test_technician_can_submit_complaint_about_their_assigned_owner(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianUser();
        Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($technicianUser)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'user',
                'complainable_id' => $owner->id,
                'subject' => 'شكوى عن مالك',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(201);
    }

    public function test_user_cannot_submit_complaint_about_self(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/complaints', [
                'complainable_type' => 'user',
                'complainable_id' => $owner->id,
                'subject' => 'شكوى',
                'description' => 'تفاصيل.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('complainable_id');
    }

    public function test_owner_can_move_own_related_complaint_to_in_progress(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => Generator::class,
            'complainable_id' => $generator->id,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/complaints/{$complaint->id}/status", ['status' => 'in_progress'])
            ->assertOk();

        $this->assertSame('in_progress', $complaint->fresh()->status->value);
    }

    public function test_resolution_note_required_when_marking_resolved(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => Generator::class,
            'complainable_id' => $generator->id,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/complaints/{$complaint->id}/status", ['status' => 'resolved'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('resolution_note');
    }

    public function test_resolving_sets_resolved_by_and_resolved_at(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => Generator::class,
            'complainable_id' => $generator->id,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/complaints/{$complaint->id}/status", [
                'status' => 'resolved',
                'resolution_note' => 'تم حل المشكلة بزيارة فنية.',
            ])
            ->assertOk();

        $fresh = $complaint->fresh();
        $this->assertSame('resolved', $fresh->status->value);
        $this->assertSame($owner->id, $fresh->resolved_by);
        $this->assertNotNull($fresh->resolved_at);
    }

    public function test_owner_cannot_resolve_unrelated_complaint(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $otherOwner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => Generator::class,
            'complainable_id' => $generator->id,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/complaints/{$complaint->id}/status", ['status' => 'in_progress'])
            ->assertStatus(403);
    }

    public function test_admin_can_resolve_any_complaint(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => Generator::class,
            'complainable_id' => $generator->id,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/complaints/{$complaint->id}/status", ['status' => 'in_progress'])
            ->assertOk();
    }

    public function test_subscriber_cannot_resolve_complaint(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => Generator::class,
            'complainable_id' => $generator->id,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($subscriberUser)
            ->patchJson("/api/v1/complaints/{$complaint->id}/status", ['status' => 'in_progress'])
            ->assertStatus(403);
    }

    public function test_submitter_can_view_own_complaint(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى عامة',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/complaints/{$complaint->id}")
            ->assertOk();
    }

    public function test_owner_can_view_complaint_related_to_their_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => Generator::class,
            'complainable_id' => $generator->id,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/complaints/{$complaint->id}")
            ->assertOk();
    }

    public function test_unrelated_user_cannot_view_complaint(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى عامة',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        [, $unrelatedSubscriberUser] = $this->makeConnectedSubscriber(
            Generator::factory()->create(['owner_id' => $this->makeOwner()->id])
        );

        $this->actingAs($unrelatedSubscriberUser)
            ->getJson("/api/v1/complaints/{$complaint->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_view_any_complaint(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->getJson("/api/v1/complaints/{$complaint->id}")
            ->assertOk();
    }

    public function test_index_excludes_unrelated_complaints_for_non_admin(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $ownComplaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى المشترك',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        [, $unrelatedSubscriberUser] = $this->makeConnectedSubscriber(
            Generator::factory()->create(['owner_id' => $this->makeOwner()->id])
        );
        Complaint::create([
            'submitted_by' => $unrelatedSubscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى غير ذات صلة',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($subscriberUser)
            ->getJson('/api/v1/complaints');

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($ownComplaint->id));
        $this->assertCount(1, $ids);
    }

    public function test_admin_can_delete_complaint(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $subscriberUser] = $this->makeConnectedSubscriber(Generator::factory()->create(['owner_id' => $owner->id]));

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/complaints/{$complaint->id}")
            ->assertOk();

        $this->assertSoftDeleted('complaints', ['id' => $complaint->id]);
    }

    public function test_owner_cannot_delete_complaint(): void
    {
        $owner = $this->makeOwner();
        [, $subscriberUser] = $this->makeConnectedSubscriber(Generator::factory()->create(['owner_id' => $owner->id]));

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/complaints/{$complaint->id}")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_complaints(): void
    {
        $this->getJson('/api/v1/complaints')->assertStatus(401);
    }

    /* ---------------------------------------------------------------
     | Type counts (تدقيق شامل — D6: توزيع حقيقي بدل بيانات وهمية ثابتة)
     |--------------------------------------------------------------- */

    public function test_admin_can_view_complaint_type_counts(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => 'App\\Models\\Generator',
            'complainable_id' => $generator->id,
            'subject' => 'شكوى 1', 'description' => 'تفاصيل', 'status' => 'pending',
        ]);
        Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => 'App\\Models\\Generator',
            'complainable_id' => $generator->id,
            'subject' => 'شكوى 2', 'description' => 'تفاصيل', 'status' => 'pending',
        ]);
        Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى عامة', 'description' => 'تفاصيل', 'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/v1/complaints/type-counts');

        $response->assertOk();
        $this->assertSame(2, $response->json('data.Generator'));
        $this->assertSame(['Generator' => 2], $response->json('data'));
    }

    public function test_complaint_type_counts_excludes_complaints_older_than_30_days(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $old = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => 'App\\Models\\Generator',
            'complainable_id' => $generator->id,
            'subject' => 'شكوى قديمة', 'description' => 'تفاصيل', 'status' => 'pending',
        ]);
        $old->created_at = now()->subDays(45);
        $old->save();

        $response = $this->actingAs($admin)->getJson('/api/v1/complaints/type-counts');

        $response->assertOk();
        $this->assertArrayNotHasKey('Generator', $response->json('data'));
    }

    public function test_owner_only_sees_type_counts_scoped_to_own_generators(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $otherOwner = $this->makeOwner();
        $otherGenerator = Generator::factory()->create(['owner_id' => $otherOwner->id]);
        [, $otherSubscriberUser] = $this->makeConnectedSubscriber($otherGenerator);

        Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => 'App\\Models\\Generator',
            'complainable_id' => $generator->id,
            'subject' => 'شكوى مولد المالك', 'description' => 'تفاصيل', 'status' => 'pending',
        ]);
        Complaint::create([
            'submitted_by' => $otherSubscriberUser->id,
            'complainable_type' => 'App\\Models\\Generator',
            'complainable_id' => $otherGenerator->id,
            'subject' => 'شكوى مولد مالك آخر', 'description' => 'تفاصيل', 'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->getJson('/api/v1/complaints/type-counts');

        $response->assertOk();
        $this->assertSame(1, $response->json('data.Generator'));
    }

    public function test_user_without_any_role_cannot_view_type_counts(): void
    {
        $roleless = User::factory()->create();

        $this->actingAs($roleless)
            ->getJson('/api/v1/complaints/type-counts')
            ->assertStatus(403);
    }

    /* ---------------------------------------------------------------
     | Assignment (بند 18 — تعيين "المسؤول" عن الشكوى)
     |--------------------------------------------------------------- */

    public function test_admin_can_assign_complaint_to_another_admin(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $subscriberUser] = $this->makeConnectedSubscriber(Generator::factory()->create(['owner_id' => $owner->id]));

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/complaints/{$complaint->id}/assign", ['assigned_to' => $otherAdmin->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_to.id', $otherAdmin->id);

        $this->assertSame($otherAdmin->id, $complaint->fresh()->assigned_to);
    }

    public function test_admin_can_unassign_complaint(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $subscriberUser] = $this->makeConnectedSubscriber(Generator::factory()->create(['owner_id' => $owner->id]));

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
            'assigned_to' => $otherAdmin->id,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/complaints/{$complaint->id}/assign", ['assigned_to' => null])
            ->assertOk();

        $this->assertNull($complaint->fresh()->assigned_to);
    }

    public function test_non_admin_cannot_assign_complaint(): void
    {
        $owner = $this->makeOwner();
        $admin = $this->makeAdmin();
        [, $subscriberUser] = $this->makeConnectedSubscriber(Generator::factory()->create(['owner_id' => $owner->id]));

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/complaints/{$complaint->id}/assign", ['assigned_to' => $admin->id])
            ->assertStatus(403);
    }

    public function test_assign_rejects_non_admin_target(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $subscriberUser] = $this->makeConnectedSubscriber(Generator::factory()->create(['owner_id' => $owner->id]));

        $complaint = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/complaints/{$complaint->id}/assign", ['assigned_to' => $owner->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_to');
    }

    /* ---------------------------------------------------------------
     | Index filters (channel / priority / assigned_to)
     |--------------------------------------------------------------- */

    public function test_index_filters_by_channel_priority_and_assigned_to(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $subscriberUser] = $this->makeConnectedSubscriber(Generator::factory()->create(['owner_id' => $owner->id]));

        $target = Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى واتساب عاجلة',
            'description' => 'تفاصيل.',
            'status' => 'pending',
            'channel' => 'whatsapp',
            'priority' => 'urgent',
            'assigned_to' => $otherAdmin->id,
        ]);

        Complaint::create([
            'submitted_by' => $subscriberUser->id,
            'complainable_type' => null,
            'subject' => 'شكوى أخرى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
            'channel' => 'app',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($admin)->getJson(
            '/api/v1/complaints?channel=whatsapp&priority=urgent&assigned_to='.$otherAdmin->id
        );

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertSame([$target->id], $ids->all());
    }

    /* ---------------------------------------------------------------
     | Export (GAP 02 — ComplaintsExport: search/status/date range)
     |---------------------------------------------------------------*/

    public function test_admin_can_export_all_complaints_without_filters(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $submitter = User::factory()->create();

        Complaint::create([
            'submitted_by' => $submitter->id,
            'subject' => 'شكوى أولى',
            'description' => 'تفاصيل.',
            'status' => 'pending',
        ]);
        Complaint::create([
            'submitted_by' => $submitter->id,
            'subject' => 'شكوى ثانية',
            'description' => 'تفاصيل.',
            'status' => 'resolved',
        ]);

        $this->actingAs($admin)->get('/api/v1/complaints/export')->assertOk();

        Excel::assertDownloaded(
            'complaints-'.now()->format('Y-m-d').'.xlsx',
            fn (ComplaintsExport $export) => $export->query()->count() === 2
        );
    }

    public function test_export_respects_status_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $submitter = User::factory()->create();

        Complaint::create([
            'submitted_by' => $submitter->id, 'subject' => 'أ', 'description' => 'د', 'status' => 'pending',
        ]);
        Complaint::create([
            'submitted_by' => $submitter->id, 'subject' => 'ب', 'description' => 'د', 'status' => 'resolved',
        ]);

        $this->actingAs($admin)
            ->get('/api/v1/complaints/export?status=resolved')
            ->assertOk();

        Excel::assertDownloaded(
            'complaints-'.now()->format('Y-m-d').'.xlsx',
            function (ComplaintsExport $export) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->status->value === 'resolved';
            }
        );
    }

    public function test_export_respects_date_range_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $submitter = User::factory()->create();

        $old = Complaint::create([
            'submitted_by' => $submitter->id, 'subject' => 'قديمة', 'description' => 'د', 'status' => 'pending',
        ]);
        $old->forceFill(['created_at' => now()->subDays(30)])->save();

        Complaint::create([
            'submitted_by' => $submitter->id, 'subject' => 'حديثة', 'description' => 'د', 'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get('/api/v1/complaints/export?date_from='.now()->subDays(2)->toDateString())
            ->assertOk();

        Excel::assertDownloaded(
            'complaints-'.now()->format('Y-m-d').'.xlsx',
            function (ComplaintsExport $export) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->subject === 'حديثة';
            }
        );
    }

    public function test_export_with_search_and_status_combined_returns_empty_when_no_match(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $submitter = User::factory()->create();

        Complaint::create([
            'submitted_by' => $submitter->id, 'subject' => 'عطل كهرباء', 'description' => 'د', 'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get('/api/v1/complaints/export?search=عطل&status=resolved')
            ->assertOk();

        Excel::assertDownloaded(
            'complaints-'.now()->format('Y-m-d').'.xlsx',
            fn (ComplaintsExport $export) => $export->query()->count() === 0
        );
    }

    public function test_subscriber_export_is_scoped_to_own_complaints_only(): void
    {
        Excel::fake();

        $owner = $this->makeOwner();
        [, $subscriberUser] = $this->makeConnectedSubscriber(Generator::factory()->create(['owner_id' => $owner->id]));

        Complaint::create([
            'submitted_by' => $subscriberUser->id, 'subject' => 'شكواي', 'description' => 'د', 'status' => 'pending',
        ]);
        Complaint::create([
            'submitted_by' => User::factory()->create()->id, 'subject' => 'شكوى غيري', 'description' => 'د', 'status' => 'pending',
        ]);

        $this->actingAs($subscriberUser)
            ->get('/api/v1/complaints/export')
            ->assertOk();

        Excel::assertDownloaded(
            'complaints-'.now()->format('Y-m-d').'.xlsx',
            function (ComplaintsExport $export) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->subject === 'شكواي';
            }
        );
    }

    public function test_unauthenticated_user_cannot_export_complaints(): void
    {
        $this->getJson('/api/v1/complaints/export')->assertStatus(401);
    }
}
