<?php

namespace Tests\Feature\Notification;

use App\Enums\Role as RoleEnum;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\FaultReportedNotification;
use App\Notifications\GeneratorHealthReportNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DoNotDisturbTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeOwnerWithGenerator(): array
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        return [$owner, $generator];
    }

    private function enableDndForAllDayAllWeek(User $user): void
    {
        UserPreference::insert([
            ['user_id' => $user->id, 'key' => 'dnd_enabled', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'key' => 'dnd_days', 'value' => '0,1,2,3,4,5,6', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'key' => 'dnd_start_time', 'value' => '00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'key' => 'dnd_end_time', 'value' => '23:59', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_non_critical_notification_is_suppressed_during_quiet_hours(): void
    {
        Notification::fake();

        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $this->enableDndForAllDayAllWeek($owner);

        $report = \App\Models\GeneratorHealthReport::create([
            'generator_id' => $generator->id,
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'risk_level' => 'low',
            'summary' => 'ملخص تجريبي.',
        ]);
        event(new \App\Events\GeneratorHealthReportGenerated($report));

        Notification::assertNotSentTo($owner, GeneratorHealthReportNotification::class);
    }

    public function test_critical_fault_notification_bypasses_quiet_hours(): void
    {
        Notification::fake();

        [$owner, $generator] = $this->makeOwnerWithGenerator();
        $this->enableDndForAllDayAllWeek($owner);

        $fault = Fault::create([
            'generator_id' => $generator->id,
            'source' => 'manual',
            'title' => 'عطل حرج',
            'description' => 'وصف.',
            'priority' => 'critical',
            'status' => 'pending_verification',
            'reported_at' => now(),
        ]);

        event(new \App\Events\FaultReported($fault));

        Notification::assertSentTo($owner, FaultReportedNotification::class);
    }

    public function test_notification_is_sent_when_dnd_disabled(): void
    {
        Notification::fake();

        [$owner, $generator] = $this->makeOwnerWithGenerator();

        $report = \App\Models\GeneratorHealthReport::create([
            'generator_id' => $generator->id,
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'risk_level' => 'low',
            'summary' => 'ملخص تجريبي.',
        ]);
        event(new \App\Events\GeneratorHealthReportGenerated($report));

        Notification::assertSentTo($owner, GeneratorHealthReportNotification::class);
    }

    public function test_owner_can_enable_dnd_with_valid_time_format(): void
    {
        [$owner] = $this->makeOwnerWithGenerator();

        $this->actingAs($owner)
            ->patchJson('/api/v1/preferences', [
                'preferences' => [
                    'dnd_enabled' => true,
                    'dnd_start_time' => '22:00',
                    'dnd_end_time' => '07:00',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.dnd_enabled', true)
            ->assertJsonPath('data.dnd_start_time', '22:00');
    }

    public function test_invalid_time_format_is_rejected(): void
    {
        [$owner] = $this->makeOwnerWithGenerator();

        $this->actingAs($owner)
            ->patchJson('/api/v1/preferences', [
                'preferences' => ['dnd_start_time' => '25:99'],
            ])
            ->assertStatus(422);
    }

    public function test_invalid_dnd_days_format_is_rejected(): void
    {
        [$owner] = $this->makeOwnerWithGenerator();

        $this->actingAs($owner)
            ->patchJson('/api/v1/preferences', [
                'preferences' => ['dnd_days' => '0,9,15'],
            ])
            ->assertStatus(422);
    }
}