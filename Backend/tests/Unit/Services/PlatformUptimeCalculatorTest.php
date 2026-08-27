<?php

namespace Tests\Unit\Services;

use App\Models\Generator;
use App\Services\PlatformUptimeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlatformUptimeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private PlatformUptimeCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new PlatformUptimeCalculator;

        Carbon::setTestNow(Carbon::parse('2026-08-13 00:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeActiveGenerator(?Carbon $verifiedAt): Generator
    {
        $generator = Generator::factory()->create(['status' => 'active']);

        $generator->forceFill(['verified_at' => $verifiedAt])->save();

        return $generator;
    }

    public function test_returns_full_uptime_when_no_active_generators_exist(): void
    {
        $uptime = $this->calculator->calculate(periodDays: 30);

        $this->assertSame(100.0, $uptime);
    }

    public function test_returns_full_uptime_when_active_generators_have_no_faults(): void
    {
        $this->makeActiveGenerator(now()->subDays(60));

        $uptime = $this->calculator->calculate(periodDays: 30);

        $this->assertSame(100.0, $uptime);
    }

    public function test_unconfirmed_fault_statuses_do_not_count_as_downtime(): void
    {
        $generator = $this->makeActiveGenerator(now()->subDays(60));

        $generator->faults()->create([
            'status' => 'pending_verification',
            'title' => 'عطل غير مؤكَّد',
            'reported_at' => now()->subDays(10),
        ]);

        $generator->faults()->create([
            'status' => 'rejected',
            'title' => 'عطل مرفوض',
            'reported_at' => now()->subDays(5),
            'resolved_at' => now()->subDays(4),
        ]);

        $uptime = $this->calculator->calculate(periodDays: 30);

        $this->assertSame(100.0, $uptime);
    }

    public function test_confirmed_resolved_fault_reduces_uptime_proportionally(): void
    {
        $generator = $this->makeActiveGenerator(now()->subDays(30));

        $generator->faults()->create([
            'status' => 'resolved',
            'title' => 'عطل مؤكَّد ومُصلَح',
            'reported_at' => now()->subDays(20),
            'verified_at' => now()->subDays(20),
            'resolved_at' => now()->subDays(17),
        ]);

        $uptime = $this->calculator->calculate(periodDays: 30);

        $this->assertEqualsWithDelta(90.0, $uptime, 0.2);
    }

    public function test_still_open_fault_counts_as_downtime_until_now(): void
    {
        $generator = $this->makeActiveGenerator(now()->subDays(30));

        $generator->faults()->create([
            'status' => 'in_repair',
            'title' => 'عطل قيد الإصلاح حاليًا',
            'reported_at' => now()->subDays(3),
            'verified_at' => now()->subDays(3),
        ]);

        $uptime = $this->calculator->calculate(periodDays: 30);

        $this->assertEqualsWithDelta(90.0, $uptime, 0.2);
    }

    public function test_downtime_before_generator_verification_is_clipped_and_not_counted(): void
    {
        $generator = $this->makeActiveGenerator(now()->subDays(5));

        $uptime = $this->calculator->calculate(periodDays: 30);

        $this->assertSame(100.0, $uptime);
    }

    public function test_generator_verified_after_period_end_is_excluded_entirely(): void
    {
        $generator = $this->makeActiveGenerator(now());

        $uptime = $this->calculator->calculate(periodDays: 30);

        $this->assertSame(100.0, $uptime);
    }

    public function test_maintenance_status_generator_is_excluded_from_calculation(): void
    {
        $generator = Generator::factory()->create(['status' => 'maintenance']);
        $generator->forceFill(['verified_at' => now()->subDays(60)])->save();

        $generator->faults()->create([
            'status' => 'in_repair',
            'title' => 'عطل على مولد تحت الصيانة',
            'reported_at' => now()->subDays(10),
            'verified_at' => now()->subDays(10),
        ]);

        $uptime = $this->calculator->calculate(periodDays: 30);

        $this->assertSame(100.0, $uptime);
    }

    public function test_aggregates_downtime_across_multiple_generators_by_total_seconds(): void
    {
        $this->makeActiveGenerator(now()->subDays(30));

        $generatorTwo = $this->makeActiveGenerator(now()->subDays(30));
        $generatorTwo->faults()->create([
            'status' => 'resolved',
            'title' => 'عطل على المولد الثاني',
            'reported_at' => now()->subDays(15),
            'verified_at' => now()->subDays(15),
            'resolved_at' => now()->subDays(9),
        ]);

        $uptime = $this->calculator->calculate(periodDays: 30);

        $this->assertEqualsWithDelta(90.0, $uptime, 0.2);
    }
}
