<?php

namespace Tests\Feature\Generator;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\User;
use App\Notifications\GeneratorRejectedNotification;
use App\Notifications\GeneratorVerifiedNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GeneratorVerificationTest extends TestCase
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

    public function test_generator_created_by_owner_starts_pending_verification(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)->postJson('/api/v1/generators', [
            'name' => 'مولد جديد',
            'price_per_kw' => 2.5,
            'currency' => 'ILS',
            'operating_schedule' => '24h',
        ]);

        $response->assertStatus(201);
        $this->assertSame('pending_verification', $response->json('data.status'));
    }

    public function test_generator_created_by_admin_is_active_immediately(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $response = $this->actingAs($admin)->postJson('/api/v1/generators', [
            'name' => 'مولد بالنيابة',
            'price_per_kw' => 2.5,
            'currency' => 'ILS',
            'operating_schedule' => '24h',
            'owner_id' => $owner->id,
        ]);

        $response->assertStatus(201);
        $this->assertSame('active', $response->json('data.status'));
    }

    public function test_admin_can_verify_pending_generator(): void
    {
        Notification::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'pending_verification',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/generators/{$generator->id}/verify");

        $response->assertOk();
        $this->assertSame('active', $generator->fresh()->status->value);
        $this->assertSame($admin->id, $generator->fresh()->verified_by);
        $this->assertNotNull($generator->fresh()->verified_at);

        Notification::assertSentTo($owner, GeneratorVerifiedNotification::class);
    }

    public function test_owner_cannot_verify_own_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'pending_verification',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/generators/{$generator->id}/verify")
            ->assertStatus(403);

        $this->assertSame('pending_verification', $generator->fresh()->status->value);
    }

    public function test_cannot_verify_already_active_generator(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/generators/{$generator->id}/verify")
            ->assertStatus(422);
    }

    public function test_admin_can_reject_pending_generator_with_reason(): void
    {
        Notification::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'pending_verification',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/generators/{$generator->id}/reject", [
                'reason' => 'رخصة التشغيل غير واضحة، يرجى إعادة الرفع.',
            ]);

        $response->assertOk();
        $this->assertSame('rejected', $generator->fresh()->status->value);
        $this->assertSame('رخصة التشغيل غير واضحة، يرجى إعادة الرفع.', $generator->fresh()->rejection_reason);

        Notification::assertSentTo($owner, GeneratorRejectedNotification::class);
    }

    public function test_reject_requires_reason(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'pending_verification',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/generators/{$generator->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_pending_generator_is_excluded_from_available_list(): void
    {
        $owner = $this->makeOwner();
        Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'pending_verification']);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);

        $response = $this->actingAs($subscriberUser)->getJson('/api/v1/generators/available');

        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }

    public function test_owner_can_still_view_own_pending_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'pending_verification']);

        $this->actingAs($owner)
            ->getJson("/api/v1/generators/{$generator->id}")
            ->assertOk();
    }
}
