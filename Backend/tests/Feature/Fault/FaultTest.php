<?php

namespace Tests\Feature\Fault;

use App\Enums\Role as RoleEnum;
use App\Exports\FaultsExport;
use App\Models\Fault;
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

class FaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeOwnerWithGenerator(): array
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        return [$owner, $generator];
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    private function makeInternalTechnician(User $owner): array
    {
        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);

        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
        ]);

        return [$technicianUser, $technician];
    }

    public function test_subscriber_can_report_fault(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);

        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        Subscription::factory()->create([
            'generator_id' => $generator->id,
            'subscriber_meter_id' => $meter->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($subscriberUser)->postJson('/api/v1/faults', [
            'generator_id' => $generator->id,
            'title' => 'المولد متوقف',
            'description' => 'المولد توقف فجأة بدون إنذار.',
            'priority' => 'high',
        ]);

        $response->assertStatus(201);
        $this->assertSame('pending_verification', $response->json('data.status'));
        $this->assertDatabaseHas('faults', ['generator_id' => $generator->id, 'status' => 'pending_verification']);
    }

    public function test_owner_can_verify_fault_as_valid(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->create(['generator_id' => $generator->id]);

        $response = $this->actingAs($owner)->patchJson("/api/v1/faults/{$fault->id}/verify", [
            'is_valid' => true,
        ]);

        $response->assertOk();
        $this->assertSame('verified', $fault->fresh()->status->value);
        $this->assertSame($owner->id, $fault->fresh()->verified_by);
    }

    public function test_owner_can_reject_fault_as_false_alarm(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->create(['generator_id' => $generator->id]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/faults/{$fault->id}/verify", ['is_valid' => false])
            ->assertOk();

        $this->assertSame('rejected', $fault->fresh()->status->value);
    }

    public function test_owner_cannot_verify_other_owners_fault(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->create(['generator_id' => $generator->id]);

        $unrelatedOwner = User::factory()->create();
        $unrelatedOwner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($unrelatedOwner)
            ->patchJson("/api/v1/faults/{$fault->id}/verify", ['is_valid' => true])
            ->assertStatus(403);
    }

    public function test_cannot_verify_already_verified_fault(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->verified()->create(['generator_id' => $generator->id]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/faults/{$fault->id}/verify", ['is_valid' => true])
            ->assertStatus(403);
    }

    public function test_cannot_decide_repair_before_verification(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->create(['generator_id' => $generator->id]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/faults/{$fault->id}/decide-repair", [
                'repair_method' => 'owner_fixed',
            ])
            ->assertStatus(403);
    }

    public function test_owner_fixed_closes_fault_directly_without_technician(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->verified()->create(['generator_id' => $generator->id]);

        $response = $this->actingAs($owner)->patchJson("/api/v1/faults/{$fault->id}/decide-repair", [
            'repair_method' => 'owner_fixed',
        ]);

        $response->assertOk();
        $fault->refresh();
        $this->assertSame('closed', $fault->status->value);
        $this->assertSame('owner_fixed', $fault->repair_method->value);
        $this->assertNotNull($fault->closed_at);
        $this->assertDatabaseCount('technician_tasks', 0);
    }

    public function test_internal_technician_creates_technician_task_and_moves_fault_to_in_repair(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        [$technicianUser, $technician] = $this->makeInternalTechnician($owner);

        $fault = Fault::factory()->verified()->create(['generator_id' => $generator->id]);

        $response = $this->actingAs($owner)->patchJson("/api/v1/faults/{$fault->id}/decide-repair", [
            'repair_method' => 'internal_technician',
            'technician_id' => $technician->id,
            'instructions' => 'تفقد الكابل الرئيسي.',
        ]);

        $response->assertOk();
        $fault->refresh();
        $this->assertSame('in_repair', $fault->status->value);

        $this->assertDatabaseHas('technician_tasks', [
            'taskable_type' => Fault::class,
            'taskable_id' => $fault->id,
            'technician_id' => $technician->id,
            'type' => 'fault_repair',
            'status' => 'assigned',
        ]);
    }

    public function test_internal_technician_without_id_creates_unassigned_task(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->verified()->create(['generator_id' => $generator->id]);

        $response = $this->actingAs($owner)->patchJson("/api/v1/faults/{$fault->id}/decide-repair", [
            'repair_method' => 'internal_technician',
        ]);

        $response->assertOk();
        $this->assertSame('in_repair', $fault->fresh()->status->value);

        $this->assertDatabaseHas('technician_tasks', [
            'taskable_type' => Fault::class,
            'taskable_id' => $fault->id,
            'technician_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_platform_technician_repair_method_value_is_rejected(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->verified()->create(['generator_id' => $generator->id]);

        $response = $this->actingAs($owner)->patchJson("/api/v1/faults/{$fault->id}/decide-repair", [
            'repair_method' => 'platform_technician',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('repair_method');
    }

    public function test_internal_technician_must_belong_to_same_owner(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        [$otherOwner] = $this->makeOwnerWithGenerator();
        [$technicianUser, $foreignTechnician] = $this->makeInternalTechnician($otherOwner);

        $fault = Fault::factory()->verified()->create(['generator_id' => $generator->id]);

        $response = $this->actingAs($owner)->patchJson("/api/v1/faults/{$fault->id}/decide-repair", [
            'repair_method' => 'internal_technician',
            'technician_id' => $foreignTechnician->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('technician_id');
    }

    public function test_technician_submitting_task_marks_fault_resolved(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        [$technicianUser, $technician] = $this->makeInternalTechnician($owner);
        $fault = Fault::factory()->inRepair()->create(['generator_id' => $generator->id]);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'technician_id' => $technician->id,
            'requested_by' => $owner->id,
            'taskable_type' => Fault::class,
            'taskable_id' => $fault->id,
            'type' => 'fault_repair',
            'status' => 'assigned',
        ]);

        $this->actingAs($technicianUser)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/start")
            ->assertOk();

        $this->actingAs($technicianUser)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/submit", [
                'completion_notes' => 'تم استبدال الكابل التالف.',
            ])
            ->assertOk();

        $this->assertSame('resolved', $fault->fresh()->status->value);
    }

    public function test_owner_approving_task_closes_fault(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        [$technicianUser, $technician] = $this->makeInternalTechnician($owner);
        $fault = Fault::factory()->inRepair()->create(['generator_id' => $generator->id]);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'technician_id' => $technician->id,
            'requested_by' => $owner->id,
            'taskable_type' => Fault::class,
            'taskable_id' => $fault->id,
            'type' => 'fault_repair',
            'status' => 'submitted',
            'completion_notes' => 'تم الإصلاح.',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'approved',
            ])
            ->assertOk();

        $this->assertSame('closed', $fault->fresh()->status->value);
        $this->assertNotNull($fault->fresh()->closed_at);
    }

    public function test_owner_rejecting_task_reverts_fault_to_in_repair(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        [$technicianUser, $technician] = $this->makeInternalTechnician($owner);
        $fault = Fault::factory()->create([
            'generator_id' => $generator->id,
            'status' => 'resolved',
            'verified_at' => now(),
            'repair_method' => 'internal_technician',
        ]);

        $task = TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'technician_id' => $technician->id,
            'requested_by' => $owner->id,
            'taskable_type' => Fault::class,
            'taskable_id' => $fault->id,
            'type' => 'fault_repair',
            'status' => 'submitted',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-tasks/{$task->id}/review", [
                'decision' => 'rejected',
                'rejection_reason' => 'العطل ما زال موجودًا.',
            ])
            ->assertOk();

        $this->assertSame('in_repair', $fault->fresh()->status->value);
    }

    public function test_admin_can_delete_fault_without_active_tasks(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->create(['generator_id' => $generator->id]);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/faults/{$fault->id}")
            ->assertOk();

        $this->assertSoftDeleted('faults', ['id' => $fault->id]);
    }

    public function test_cannot_delete_fault_with_active_technician_task(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        [$technicianUser, $technician] = $this->makeInternalTechnician($owner);
        $fault = Fault::factory()->inRepair()->create(['generator_id' => $generator->id]);

        TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'technician_id' => $technician->id,
            'requested_by' => $owner->id,
            'taskable_type' => Fault::class,
            'taskable_id' => $fault->id,
            'type' => 'fault_repair',
            'status' => 'assigned',
        ]);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/faults/{$fault->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('faults', ['id' => $fault->id, 'deleted_at' => null]);
    }

    public function test_owner_cannot_delete_fault(): void
    {
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $fault = Fault::factory()->create(['generator_id' => $generator->id]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/faults/{$fault->id}")
            ->assertStatus(403);
    }

    /* ---------------------------------------------------------------
     | Excel export
     |---------------------------------------------------------------*/

    public function test_admin_can_export_all_faults_without_filters(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        Fault::factory()->count(2)->create(['generator_id' => $generator->id]);

        $this->actingAs($admin)->get('/api/v1/faults/export')->assertOk();

        Excel::assertDownloaded(
            'faults-'.now()->format('Y-m-d').'.xlsx',
            fn (FaultsExport $export) => $export->query()->count() === 2
        );
    }

    public function test_fault_export_respects_status_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        [$owner, $generator] = $this->makeOwnerWithGenerator();
        Fault::factory()->create(['generator_id' => $generator->id, 'status' => 'pending_verification']);
        Fault::factory()->verified()->create(['generator_id' => $generator->id]);

        $this->actingAs($admin)
            ->get('/api/v1/faults/export?status=verified')
            ->assertOk();

        Excel::assertDownloaded(
            'faults-'.now()->format('Y-m-d').'.xlsx',
            function (FaultsExport $export) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->status->value === 'verified';
            }
        );
    }

    public function test_owner_export_is_scoped_to_own_generators_only(): void
    {
        Excel::fake();

        [$owner, $generator] = $this->makeOwnerWithGenerator();
        [$otherOwner, $otherGenerator] = $this->makeOwnerWithGenerator();
        Fault::factory()->create(['generator_id' => $generator->id, 'title' => 'عطل مالكي']);
        Fault::factory()->create(['generator_id' => $otherGenerator->id, 'title' => 'عطل غيري']);

        $this->actingAs($owner)->get('/api/v1/faults/export')->assertOk();

        Excel::assertDownloaded(
            'faults-'.now()->format('Y-m-d').'.xlsx',
            function (FaultsExport $export) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->title === 'عطل مالكي';
            }
        );
    }

    public function test_unauthenticated_user_cannot_export_faults(): void
    {
        $this->getJson('/api/v1/faults/export')->assertStatus(401);
    }
}
