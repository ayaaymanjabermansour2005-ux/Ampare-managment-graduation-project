<?php

namespace App\Services;

use App\Enums\GeneratorStatus;
use App\Models\Generator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PlatformUptimeCalculator
{
    private const CONFIRMED_DOWNTIME_STATUSES = ['verified', 'in_repair', 'resolved', 'closed'];

    public function calculate(int $periodDays = 30): float
    {
        $periodEnd = CarbonImmutable::now();
        $periodStart = $periodEnd->subDays($periodDays);

        $generators = Generator::query()
            ->where('status', GeneratorStatus::Active->value)
            ->whereNotNull('verified_at')
            ->where('verified_at', '<', $periodEnd)
            ->with(['faults' => function ($query) use ($periodStart) {
                $query->whereIn('status', self::CONFIRMED_DOWNTIME_STATUSES)
                    ->where(function ($q) use ($periodStart) {
                        $q->whereNull('resolved_at')->whereNull('closed_at')
                            ->orWhere('resolved_at', '>=', $periodStart)
                            ->orWhere('closed_at', '>=', $periodStart);
                    });
            }])
            ->get(['id', 'verified_at']);

        if ($generators->isEmpty()) {
            return 100.0;
        }

        [$totalAvailableSeconds, $totalDowntimeSeconds] = $this->accumulate(
            $generators,
            $periodStart,
            $periodEnd
        );

        if ($totalAvailableSeconds <= 0) {
            return 100.0;
        }

        $uptime = (1 - ($totalDowntimeSeconds / $totalAvailableSeconds)) * 100;

        return round(max(0.0, min(100.0, $uptime)), 1);
    }

    /**
     * @return array{0: int, 1: int} [إجمالي ثواني التوفر, إجمالي ثواني التوقّف]
     */
    private function accumulate(Collection $generators, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $totalAvailableSeconds = 0;
        $totalDowntimeSeconds = 0;

        foreach ($generators as $generator) {
            $generatorStart = CarbonImmutable::parse($generator->verified_at)->max($periodStart);

            if ($generatorStart->greaterThanOrEqualTo($periodEnd)) {
                continue;
            }

            $totalAvailableSeconds += $generatorStart->diffInSeconds($periodEnd);

            foreach ($generator->faults as $fault) {
                $downStart = CarbonImmutable::parse($fault->verified_at ?? $fault->reported_at)->max($generatorStart);

                $rawDownEnd = $fault->resolved_at ?? $fault->closed_at;
                $downEnd = $rawDownEnd
                    ? CarbonImmutable::parse($rawDownEnd)->min($periodEnd)
                    : $periodEnd;

                if ($downEnd->greaterThan($downStart)) {
                    $totalDowntimeSeconds += $downStart->diffInSeconds($downEnd);
                }
            }
        }

        return [$totalAvailableSeconds, $totalDowntimeSeconds];
    }
}
