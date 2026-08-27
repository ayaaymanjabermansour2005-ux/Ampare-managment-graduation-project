<?php

namespace Tests\Feature\TechnicianPayment;

use App\Enums\Role as RoleEnum;
use App\Models\Technician;
use App\Models\TechnicianPayment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TechnicianPaymentTest extends TestCase
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

    private function makeTechnicianFor(User $owner): array
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::TECHNICIAN->value);
        $technician = Technician::factory()->create(['user_id' => $user->id, 'owner_id' => $owner->id]);

        return [$user, $technician];
    }

    public function test_owner_can_create_payment_for_own_technician(): void
    {
        $owner = $this->makeOwner();
        [$technicianUser, $technician] = $this->makeTechnicianFor($owner);

        $this->actingAs($owner)
            ->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/technician-payments', [
                'technician_id' => $technician->id,
                'amount' => 200,
                'currency' => 'ILS',
                'note' => 'أجرة الأسبوع',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.owner_id', $owner->id);

        $this->assertDatabaseHas('technician_payments', [
            'technician_id' => $technician->id,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_cannot_create_payment_for_another_owners_technician(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        [, $foreignTechnician] = $this->makeTechnicianFor($otherOwner);

        $this->actingAs($owner)
            ->postJson('/api/v1/technician-payments', [
                'technician_id' => $foreignTechnician->id,
                'amount' => 200,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('technician_payments', [
            'technician_id' => $foreignTechnician->id,
        ]);
    }

    public function test_owner_id_is_never_trusted_from_request(): void
    {
        $owner = $this->makeOwner();
        $attacker = $this->makeOwner();
        [, $technician] = $this->makeTechnicianFor($owner);

        // Even if an owner_id-like field were injected, the server always
        // derives owner_id from the technician's real owner, never from input.
        $this->actingAs($owner)
            ->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/technician-payments', [
                'technician_id' => $technician->id,
                'owner_id' => $attacker->id,
                'amount' => 150,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('technician_payments', [
            'technician_id' => $technician->id,
            'owner_id' => $owner->id,
        ]);
    }

    public function test_technician_can_approve_own_payment(): void
    {
        $owner = $this->makeOwner();
        [$technicianUser, $technician] = $this->makeTechnicianFor($owner);

        $payment = TechnicianPayment::factory()->create([
            'technician_id' => $technician->id,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($technicianUser)
            ->patchJson("/api/v1/technician-payments/{$payment->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reviewed_by', $technicianUser->id);
    }

    public function test_technician_can_reject_own_payment_with_reason(): void
    {
        $owner = $this->makeOwner();
        [$technicianUser, $technician] = $this->makeTechnicianFor($owner);

        $payment = TechnicianPayment::factory()->create([
            'technician_id' => $technician->id,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($technicianUser)
            ->patchJson("/api/v1/technician-payments/{$payment->id}/reject")
            ->assertStatus(422);

        $this->actingAs($technicianUser)
            ->patchJson("/api/v1/technician-payments/{$payment->id}/reject", [
                'reason' => 'المبلغ غير صحيح',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'المبلغ غير صحيح');
    }

    public function test_technician_cannot_approve_another_technicians_payment(): void
    {
        $owner = $this->makeOwner();
        [, $technicianA] = $this->makeTechnicianFor($owner);
        [$technicianBUser, ] = $this->makeTechnicianFor($owner);

        $payment = TechnicianPayment::factory()->create([
            'technician_id' => $technicianA->id,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($technicianBUser)
            ->patchJson("/api/v1/technician-payments/{$payment->id}/approve")
            ->assertStatus(403);
    }

    public function test_owner_cannot_approve_or_reject_technician_payment(): void
    {
        $owner = $this->makeOwner();
        [, $technician] = $this->makeTechnicianFor($owner);

        $payment = TechnicianPayment::factory()->create([
            'technician_id' => $technician->id,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technician-payments/{$payment->id}/approve")
            ->assertStatus(403);
    }

    public function test_admin_can_view_but_cannot_approve_or_reject_technician_payment(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $technician] = $this->makeTechnicianFor($owner);

        $payment = TechnicianPayment::factory()->create([
            'technician_id' => $technician->id,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/technician-payments')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->getJson("/api/v1/technician-payments/{$payment->id}")
            ->assertStatus(200);

        $this->actingAs($admin)
            ->patchJson("/api/v1/technician-payments/{$payment->id}/approve")
            ->assertStatus(403);

        $this->actingAs($admin)
            ->patchJson("/api/v1/technician-payments/{$payment->id}/reject", ['reason' => 'test'])
            ->assertStatus(403);
    }

    public function test_owner_only_sees_own_technician_payments(): void
    {
        $ownerA = $this->makeOwner();
        $ownerB = $this->makeOwner();
        [, $technicianA] = $this->makeTechnicianFor($ownerA);
        [, $technicianB] = $this->makeTechnicianFor($ownerB);

        TechnicianPayment::factory()->create(['technician_id' => $technicianA->id, 'owner_id' => $ownerA->id, 'created_by' => $ownerA->id]);
        TechnicianPayment::factory()->create(['technician_id' => $technicianB->id, 'owner_id' => $ownerB->id, 'created_by' => $ownerB->id]);

        $response = $this->actingAs($ownerA)->getJson('/api/v1/technician-payments')->assertStatus(200);

        $ids = collect($response->json('data.data'))->pluck('owner_id')->unique();
        $this->assertEquals([$ownerA->id], $ids->values()->all());
    }

    public function test_technician_cannot_view_another_technicians_payment(): void
    {
        $owner = $this->makeOwner();
        [, $technicianA] = $this->makeTechnicianFor($owner);
        [$technicianBUser, ] = $this->makeTechnicianFor($owner);

        $payment = TechnicianPayment::factory()->create([
            'technician_id' => $technicianA->id,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($technicianBUser)
            ->getJson("/api/v1/technician-payments/{$payment->id}")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_technician_payments(): void
    {
        $this->getJson('/api/v1/technician-payments')->assertStatus(401);
    }

    public function test_cannot_approve_an_already_reviewed_payment(): void
    {
        $owner = $this->makeOwner();
        [$technicianUser, $technician] = $this->makeTechnicianFor($owner);

        $payment = TechnicianPayment::factory()->approved()->create([
            'technician_id' => $technician->id,
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        // Consistent with TechnicianTaskTest::test_assign_on_closed_task_is_rejected —
        // a non-reviewable state fails the object-level Policy check (403), the same
        // convention used elsewhere in this codebase for closed-state transitions.
        $this->actingAs($technicianUser)
            ->patchJson("/api/v1/technician-payments/{$payment->id}/approve")
            ->assertStatus(403);
    }
}
