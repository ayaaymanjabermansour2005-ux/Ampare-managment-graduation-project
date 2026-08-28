<?php

namespace Tests\Feature\GeneratorSchedule;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\GeneratorSchedule;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TEST-002: GeneratorSchedule had no Feature/Unit coverage at all prior to
 * this file (confirmed via a repo-wide grep before writing these tests).
 */
class GeneratorScheduleTest extends TestCase
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

    /**
     * @return array{0: Subscriber, 1: User}
     */
    private function makeSubscriberUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $user->id]);

        return [$subscriber, $user];
    }

    private function subscribeToGenerator(Subscriber $subscriber, Generator $generator): Subscription
    {
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        return Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
        ]);
    }

    // ==================== Create ====================

    public function test_owner_can_create_schedule_for_own_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->postJson("/api/v1/generators/{$generator->id}/schedules", [
                'starts_at' => now()->addDay()->setTime(18, 0)->toDateTimeString(),
                'ends_at' => now()->addDay()->setTime(23, 0)->toDateTimeString(),
                'note' => 'تشغيل مسائي',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('generator_schedules', [
            'generator_id' => $generator->id,
            'created_by' => $owner->id,
            'note' => 'تشغيل مسائي',
        ]);
    }

    public function test_owner_cannot_create_schedule_for_another_owners_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create();

        $this->actingAs($owner)
            ->postJson("/api/v1/generators/{$generator->id}/schedules", [
                'starts_at' => now()->addDay()->toDateTimeString(),
                'ends_at' => now()->addDays(2)->toDateTimeString(),
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_create_schedule_for_any_generator(): void
    {
        $admin = $this->makeAdmin();
        $generator = Generator::factory()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/generators/{$generator->id}/schedules", [
                'starts_at' => now()->addDay()->toDateTimeString(),
                'ends_at' => now()->addDays(2)->toDateTimeString(),
            ])
            ->assertStatus(201);
    }

    public function test_subscriber_cannot_create_schedule(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/generators/{$generator->id}/schedules", [
                'starts_at' => now()->addDay()->toDateTimeString(),
                'ends_at' => now()->addDays(2)->toDateTimeString(),
            ])
            ->assertStatus(403);
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/api/v1/generators/{$generator->id}/schedules", [
                'starts_at' => now()->addDay()->toDateTimeString(),
                'ends_at' => now()->toDateTimeString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_starts_at_and_ends_at_are_required(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/api/v1/generators/{$generator->id}/schedules", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    }

    /**
     * Documents CURRENT behavior only (matches the original audit's INFO-001
     * "needs verification with product owner" framing) — CreateGeneratorScheduleAction
     * performs no overlap check, so two overlapping windows for the same
     * generator are both accepted. Not changed here without a product decision.
     */
    public function test_overlapping_schedule_windows_are_currently_allowed(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/api/v1/generators/{$generator->id}/schedules", [
                'starts_at' => now()->addDay()->setTime(18, 0)->toDateTimeString(),
                'ends_at' => now()->addDay()->setTime(23, 0)->toDateTimeString(),
            ])
            ->assertStatus(201);

        $this->actingAs($owner)
            ->postJson("/api/v1/generators/{$generator->id}/schedules", [
                'starts_at' => now()->addDay()->setTime(20, 0)->toDateTimeString(),
                'ends_at' => now()->addDay()->setTime(22, 0)->toDateTimeString(),
            ])
            ->assertStatus(201);

        $this->assertSame(2, GeneratorSchedule::where('generator_id', $generator->id)->count());
    }

    // ==================== Index / visibility ====================

    public function test_owner_can_list_own_generator_schedule(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        GeneratorSchedule::factory()->create(['generator_id' => $generator->id, 'created_by' => $owner->id]);

        $response = $this->actingAs($owner)->getJson("/api/v1/generators/{$generator->id}/schedules");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_owner_cannot_list_another_owners_generator_schedule(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create();

        $this->actingAs($owner)
            ->getJson("/api/v1/generators/{$generator->id}/schedules")
            ->assertStatus(403);
    }

    public function test_subscriber_with_active_subscription_can_list_generator_schedule(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        GeneratorSchedule::factory()->create(['generator_id' => $generator->id, 'created_by' => $owner->id]);
        [$subscriber, $subscriberUser] = $this->makeSubscriberUser();
        $this->subscribeToGenerator($subscriber, $generator);

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/generators/{$generator->id}/schedules")
            ->assertOk();
    }

    public function test_subscriber_without_subscription_cannot_list_generator_schedule(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/generators/{$generator->id}/schedules")
            ->assertStatus(403);
    }

    public function test_index_excludes_schedules_that_ended_more_than_a_day_ago(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $old = GeneratorSchedule::factory()->create([
            'generator_id' => $generator->id,
            'created_by' => $owner->id,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDays(3),
        ]);
        $recentlyEnded = GeneratorSchedule::factory()->create([
            'generator_id' => $generator->id,
            'created_by' => $owner->id,
            'starts_at' => now()->subHours(5),
            'ends_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($owner)->getJson("/api/v1/generators/{$generator->id}/schedules");

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($old->id));
        $this->assertTrue($ids->contains($recentlyEnded->id));
    }

    public function test_unauthenticated_user_cannot_access_generator_schedules(): void
    {
        $generator = Generator::factory()->create();

        $this->getJson("/api/v1/generators/{$generator->id}/schedules")->assertStatus(401);
        $this->postJson("/api/v1/generators/{$generator->id}/schedules", [])->assertStatus(401);
    }

    // ==================== Update ====================

    public function test_owner_can_update_own_schedule(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $schedule = GeneratorSchedule::factory()->create(['generator_id' => $generator->id, 'created_by' => $owner->id]);

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/generator-schedules/{$schedule->id}", ['note' => 'ملاحظة محدثة']);

        $response->assertOk();
        $this->assertSame('ملاحظة محدثة', $schedule->fresh()->note);
    }

    public function test_owner_cannot_update_another_owners_schedule(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $otherOwner->id]);
        $schedule = GeneratorSchedule::factory()->create(['generator_id' => $generator->id, 'created_by' => $otherOwner->id]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/generator-schedules/{$schedule->id}", ['note' => 'محاولة تعديل'])
            ->assertStatus(403);
    }

    public function test_admin_can_update_any_schedule(): void
    {
        $admin = $this->makeAdmin();
        $schedule = GeneratorSchedule::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/v1/generator-schedules/{$schedule->id}", ['note' => 'تعديل الإدارة'])
            ->assertOk();
    }

    // ==================== Delete ====================

    public function test_owner_can_delete_own_schedule(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $schedule = GeneratorSchedule::factory()->create(['generator_id' => $generator->id, 'created_by' => $owner->id]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/generator-schedules/{$schedule->id}")
            ->assertOk();

        $this->assertDatabaseMissing('generator_schedules', ['id' => $schedule->id]);
    }

    public function test_owner_cannot_delete_another_owners_schedule(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $otherOwner->id]);
        $schedule = GeneratorSchedule::factory()->create(['generator_id' => $generator->id, 'created_by' => $otherOwner->id]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/generator-schedules/{$schedule->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('generator_schedules', ['id' => $schedule->id]);
    }

    // ==================== Model ====================

    public function test_is_active_now_reflects_the_current_time_window(): void
    {
        $active = GeneratorSchedule::factory()->create([
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        $future = GeneratorSchedule::factory()->create([
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);

        $this->assertTrue($active->isActiveNow());
        $this->assertFalse($future->isActiveNow());
    }
}
