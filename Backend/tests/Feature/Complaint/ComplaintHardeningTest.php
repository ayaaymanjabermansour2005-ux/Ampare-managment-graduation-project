<?php

namespace Tests\Feature\Complaint;

use App\Enums\Role as RoleEnum;
use App\Models\Complaint;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintHardeningTest extends TestCase
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

    public function test_cannot_update_status_of_already_resolved_complaint(): void
    {
        $admin = $this->makeAdmin();
        $subscriber = User::factory()->create();
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        $complaint = Complaint::create([
            'submitted_by' => $subscriber->id,
            'subject' => 'شكوى تجريبية',
            'description' => 'تفاصيل الشكوى.',
            'status' => 'resolved',
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
            'resolution_note' => 'تم الحل مسبقًا.',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/complaints/{$complaint->id}/status", [
                'status' => 'in_progress',
            ])
            ->assertStatus(403);

        $this->assertSame('resolved', $complaint->fresh()->status->value);
    }

    public function test_can_update_status_of_pending_complaint(): void
    {
        $admin = $this->makeAdmin();
        $subscriber = User::factory()->create();
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        $complaint = Complaint::create([
            'submitted_by' => $subscriber->id,
            'subject' => 'شكوى تجريبية',
            'description' => 'تفاصيل الشكوى.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/complaints/{$complaint->id}/status", [
                'status' => 'resolved',
                'resolution_note' => 'تم التواصل وحل المشكلة.',
            ])
            ->assertOk();

        $this->assertSame('resolved', $complaint->fresh()->status->value);
        $this->assertSame($admin->id, $complaint->fresh()->resolved_by);
    }
}
