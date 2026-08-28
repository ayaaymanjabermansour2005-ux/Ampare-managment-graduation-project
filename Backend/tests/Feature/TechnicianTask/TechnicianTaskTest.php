<?php

namespace Tests\Feature\TechnicianTask;

use App\Enums\Role as RoleEnum;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Technician;
use App\Models\TechnicianRating;
use App\Models\TechnicianTask;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TechnicianTaskTest extends TestCase
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

    private function makePrivateTechnician(User $owner, Generator $generator, bool $linkToGenerator = true): Technician
    {
        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);

        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
            'status' => 'active',
        ]);

        if ($linkToGenerator) {
            $technician->generators()->attach($generator->id);
        }

        return $technician;
    }

    private function makeGenerator(User $owner): Generator
    {
        return Generator::factory()->create(['owner_id' => $owner->id]);
    }

    private function idHeader()
    {
        return ['Idempotency-Key' => Str::uuid()->toString()];
    }

    public function test_owner_can_create_unassigned_task_with_status_pending(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        $response = $this->actingAs($owner)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/technician-tasks', [
                'generator_id' => $generator->id,
                'type' => 'general_maintenance',
                'instructions' => 'فحص دوري.',
            ]);

        $response->assertStatus(201);
        $this->assertSame('pending', $response->json('data.status'));
        $this->assertDatabaseHas('technician_tasks', [
            'generator_id' => $generator->id,
            'status' => 'pending',
            'technician_id' => null,
        ]);
    }

    public function test_owner_can_create_task_with_technician_and_status_is_assigned(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $response = $this->actingAs($owner)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/technician-tasks', [
                'generator_id' => $generator->id,
                'technician_id' => $technician->id,
                'type' => 'general_maintenance',
            ]);

        $response->assertStatus(201);
        $this->assertSame('assigned', $response->json('data.status'));
    }

    public function test_technician_role_cannot_create_task(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $this->actingAs($technician->user)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/technician-tasks', [
                'generator_id' => $generator->id,
                'type' => 'general_maintenance',
            ])
            ->assertStatus(403);
    }

    public function test_owner_cannot_create_task_for_another_owners_generator(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = $this->makeGenerator($otherOwner);

        $this->actingAs($owner)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/technician-tasks', [
                'generator_id' => $generator->id,
                'type' => 'general_maintenance',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('generator_id');
    }

    public function test_owner_cannot_assign_private_technician_linked_to_a_different_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $otherGenerator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $otherGenerator);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/assign", [
                'technician_id' => $technician->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('technician_tasks', ['id' => $task->id, 'status' => 'pending']);
    }

    public function test_owner_can_assign_private_technician_with_no_explicit_generator_links(): void
    {
        // فني بدون أي روابط صريحة لأي مولد يُعتبر متاحًا لجميع مولدات
        // مالكه — نفس القاعدة المستخدمة عند إنشاء المهمة وعند اقتراح
        // الفنيين (TechnicianEligibilityChecker / availableForGenerator).
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator, linkToGenerator: false);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/assign", [
                'technician_id' => $technician->id,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('technician_tasks', [
            'id' => $task->id,
            'status' => 'assigned',
            'technician_id' => $technician->id,
        ]);
    }

    public function test_owner_cannot_assign_another_owners_private_technician(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        $foreignTechnicianUser = User::factory()->create();
        $foreignTechnicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        $foreignTechnician = Technician::factory()->create([
            'user_id' => $foreignTechnicianUser->id,
            'owner_id' => $otherOwner->id,
            'status' => 'active',
        ]);
        $foreignTechnician->generators()->attach($generator->id);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/assign", [
                'technician_id' => $foreignTechnician->id,
            ])
            ->assertStatus(422);
    }

    public function test_assign_on_closed_task_is_rejected(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'status' => 'cancelled',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/assign", [
                'technician_id' => $technician->id,
            ])
            ->assertStatus(403);
    }

    public function test_full_state_machine_from_assigned_to_approved(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $task = TechnicianTask::factory()->assigned()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $technician->id,
            'assigned_by' => $owner->id,
        ]);

        $this->actingAs($technician->user)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/on-the-way")
            ->assertOk();
        $this->assertSame('on_the_way', $task->fresh()->status->value);

        $this->actingAs($technician->user)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/start")
            ->assertOk();
        $this->assertSame('in_progress', $task->fresh()->status->value);

        $this->actingAs($technician->user)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/waiting-parts")
            ->assertOk();
        $this->assertSame('waiting_parts', $task->fresh()->status->value);

        $this->actingAs($technician->user)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/start")
            ->assertOk();
        $this->assertSame('in_progress', $task->fresh()->status->value);

        $this->actingAs($technician->user)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/submit", [
                'completion_notes' => 'تم استبدال القطعة التالفة.',
            ])
            ->assertOk();
        $this->assertSame('submitted', $task->fresh()->status->value);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'approved',
            ])
            ->assertOk();

        $fresh = $task->fresh();
        $this->assertSame('approved', $fresh->status->value);
        $this->assertSame('owner', $fresh->reviewer_role->value);
        $this->assertSame($owner->id, $fresh->reviewed_by);
    }

    public function test_owner_can_reject_submitted_task_with_reason(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $task = TechnicianTask::factory()->inProgress()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $technician->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'rejected',
                'rejection_reason' => 'الإصلاح غير مكتمل.',
            ])
            ->assertOk();

        $fresh = $task->fresh();
        $this->assertSame('rejected', $fresh->status->value);
        $this->assertSame('الإصلاح غير مكتمل.', $fresh->rejection_reason);
    }

    public function test_admin_review_requires_override_reason(): void
    {
        $owner = $this->makeOwner();
        $admin = $this->makeAdmin();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $technician->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'approved',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('admin_override_reason');

        // معها → ينجح ويسجّل reviewer_role = admin
        $this->actingAs($admin)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'approved',
                'admin_override_reason' => 'الأونر غير متجاوب، تدخّل إداري.',
            ])
            ->assertOk();

        $fresh = $task->fresh();
        $this->assertSame('admin', $fresh->reviewer_role->value);
        $this->assertSame('الأونر غير متجاوب، تدخّل إداري.', $fresh->admin_override_reason);
    }

    public function test_submit_from_non_in_progress_status_is_rejected(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $task = TechnicianTask::factory()->assigned()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $technician->id,
        ]);

        $this->actingAs($technician->user)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/submit", [
                'completion_notes' => 'محاولة تسليم من حالة خاطئة.',
            ])
            ->assertStatus(403);

        $this->assertSame('assigned', $task->fresh()->status->value);
    }

    public function test_review_of_non_submitted_task_is_rejected(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'status' => 'approved',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'approved',
            ])
            ->assertStatus(403);
    }

    public function test_cancel_submitted_task_is_rejected(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/cancel")
            ->assertStatus(403);

        $this->assertSame('submitted', $task->fresh()->status->value);
    }

    public function test_owner_can_cancel_active_task(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/cancel")
            ->assertOk();

        $this->assertSame('cancelled', $task->fresh()->status->value);
    }

    public function test_technician_cannot_act_on_task_assigned_to_another_technician(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $assignedTechnician = $this->makePrivateTechnician($owner, $generator);
        $otherTechnician = $this->makePrivateTechnician($owner, $generator);

        $task = TechnicianTask::factory()->assigned()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $assignedTechnician->id,
        ]);

        $this->actingAs($otherTechnician->user)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/on-the-way")
            ->assertStatus(403);

        $this->assertSame('assigned', $task->fresh()->status->value);
    }

    public function test_owner_cannot_review_another_owners_task(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = $this->makeGenerator($otherOwner);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $otherOwner->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'approved',
            ])
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_technician_tasks(): void
    {
        $this->getJson('/api/v1/technician-tasks')->assertStatus(401);
    }

    public function test_submitting_fault_linked_task_marks_fault_as_resolved(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $fault = Fault::factory()->inRepair()->create(['generator_id' => $generator->id]);

        $task = TechnicianTask::factory()->inProgress()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $technician->id,
            'taskable_type' => Fault::class,
            'taskable_id' => $fault->id,
            'type' => 'fault_repair',
        ]);

        $this->actingAs($technician->user)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/submit", [
                'completion_notes' => 'تم الإصلاح.',
            ])
            ->assertOk();

        $this->assertSame('resolved', $fault->fresh()->status->value);
        $this->assertNotNull($fault->fresh()->resolved_at);
    }

    public function test_approving_fault_linked_task_closes_the_fault(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $fault = Fault::factory()->inRepair()->create(['generator_id' => $generator->id]);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $technician->id,
            'taskable_type' => Fault::class,
            'taskable_id' => $fault->id,
            'type' => 'fault_repair',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'approved',
            ])
            ->assertOk();

        $freshFault = $fault->fresh();
        $this->assertSame('closed', $freshFault->status->value);
        $this->assertSame($owner->id, $freshFault->closed_by);
        $this->assertNotNull($freshFault->closed_at);
    }

    public function test_rejecting_fault_linked_task_reverts_fault_to_in_repair(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);

        $fault = Fault::factory()->inRepair()->create([
            'generator_id' => $generator->id,
            'resolved_at' => now(),
        ]);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $technician->id,
            'taskable_type' => Fault::class,
            'taskable_id' => $fault->id,
            'type' => 'fault_repair',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'rejected',
                'rejection_reason' => 'العطل لسا موجود.',
            ])
            ->assertOk();

        $freshFault = $fault->fresh();
        $this->assertSame('in_repair', $freshFault->status->value);
        $this->assertNull($freshFault->resolved_at);
    }

    public function test_fault_repair_task_created_via_api_syncs_fault_status_on_submit(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);
        $fault = Fault::factory()->inRepair()->create(['generator_id' => $generator->id]);

        $createResponse = $this->actingAs($owner)
            ->withHeaders($this->idHeader())
            ->postJson('/api/v1/technician-tasks', [
                'generator_id' => $generator->id,
                'technician_id' => $technician->id,
                'type' => 'fault_repair',
                'taskable_type' => 'fault',
                'taskable_id' => $fault->id,
            ]);

        $createResponse->assertStatus(201);
        $taskId = $createResponse->json('data.id');

        TechnicianTask::whereKey($taskId)->update(['status' => 'in_progress', 'started_at' => now()]);

        $this->actingAs($technician->user)
            ->patchJson("/api/v1/technician-tasks/{$taskId}/submit", [
                'completion_notes' => 'تم الإصلاح عبر الـ API الكامل.',
            ])
            ->assertOk();

        $this->assertSame(
            'resolved',
            $fault->fresh()->status->value,
            'فشل هذا الاعتراض متوقّع لحد ما يتم تسجيل Relation::morphMap وتعديل FaultTaskSynchronizer لاستخدام instanceof — راجع التعليق أعلى الدالة.'
        );
    }

    // ==================== TEST-002: TechnicianRating coverage (had zero tests before this) ====================

    private function makeApprovedTask(User $owner, Generator $generator, Technician $technician): TechnicianTask
    {
        return TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $technician->id,
            'assigned_by' => $owner->id,
            'status' => 'approved',
        ]);
    }

    public function test_owner_can_rate_an_approved_task_for_own_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);
        $task = $this->makeApprovedTask($owner, $generator, $technician);

        $response = $this->actingAs($owner)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", [
                'rating' => 5,
                'comment' => 'عمل ممتاز.',
            ]);

        $response->assertStatus(201);
        $this->assertSame(5, $response->json('data.rating'));
        $this->assertDatabaseHas('technician_ratings', [
            'technician_task_id' => $task->id,
            'technician_id' => $technician->id,
            'rated_by' => $owner->id,
            'rating' => 5,
        ]);
    }

    public function test_admin_can_rate_any_approved_task(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);
        $task = $this->makeApprovedTask($owner, $generator, $technician);

        $this->actingAs($admin)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 4])
            ->assertStatus(201);
    }

    public function test_owner_cannot_rate_another_owners_task(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = $this->makeGenerator($otherOwner);
        $technician = $this->makePrivateTechnician($otherOwner, $generator);
        $task = $this->makeApprovedTask($otherOwner, $generator, $technician);

        $this->actingAs($owner)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 3])
            ->assertStatus(403);
    }

    public function test_technician_cannot_rate_their_own_task(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);
        $task = $this->makeApprovedTask($owner, $generator, $technician);

        $this->actingAs($technician->user)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 5])
            ->assertStatus(403);
    }

    public function test_cannot_rate_a_task_that_is_not_yet_approved(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);
        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'requested_by' => $owner->id,
            'technician_id' => $technician->id,
            'assigned_by' => $owner->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($owner)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 5])
            ->assertStatus(403);

        $this->assertDatabaseMissing('technician_ratings', ['technician_task_id' => $task->id]);
    }

    /**
     * A second rating attempt is blocked at the policy level (`rate()`
     * returns false once `$task->rating()->exists()`), so the response is a
     * 403, not a 422 — the Action's own duplicate check
     * (RateTechnicianTaskAction::execute) is unreachable defense-in-depth,
     * not the live code path. Documenting the actual observed behavior here.
     */
    public function test_cannot_rate_the_same_task_twice(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);
        $task = $this->makeApprovedTask($owner, $generator, $technician);

        $this->actingAs($owner)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 5])
            ->assertStatus(201);

        $this->actingAs($owner)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 2])
            ->assertStatus(403);

        $this->assertSame(1, TechnicianRating::where('technician_task_id', $task->id)->count());
    }

    public function test_rating_value_must_be_between_1_and_5(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);
        $task = $this->makeApprovedTask($owner, $generator, $technician);

        $this->actingAs($owner)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rating');

        $this->actingAs($owner)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 6])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rating');
    }

    public function test_rating_comment_is_optional(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);
        $task = $this->makeApprovedTask($owner, $generator, $technician);

        $this->actingAs($owner)
            ->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 3])
            ->assertStatus(201)
            ->assertJsonPath('data.comment', null);
    }

    public function test_unauthenticated_user_cannot_rate_task(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $technician = $this->makePrivateTechnician($owner, $generator);
        $task = $this->makeApprovedTask($owner, $generator, $technician);

        $this->postJson("/api/v1/technician-tasks/{$task->id}/rate", ['rating' => 5])
            ->assertStatus(401);
    }
}
