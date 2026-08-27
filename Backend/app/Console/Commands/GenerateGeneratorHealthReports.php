<?php

namespace App\Console\Commands;

use App\Actions\Generator\GenerateGeneratorHealthReportAction;
use App\Enums\GeneratorStatus;
use App\Events\GeneratorHealthReportGenerated;
use App\Models\Generator;
use Illuminate\Console\Command;

class GenerateGeneratorHealthReports extends Command
{
    protected $signature = 'generators:health-report {--period=monthly : monthly|weekly}';

    protected $description = 'يولّد تقرير صحة دوري لكل مولد نشط عبر الذكاء الاصطناعي';

    public function handle(GenerateGeneratorHealthReportAction $action): int
    {
        $isWeekly = $this->option('period') === 'weekly';
        $periodStart = $isWeekly ? now()->subWeek() : now()->subMonth();
        $periodEnd = now();
        $count = 0;

        Generator::where('status', GeneratorStatus::Active->value)->each(function (Generator $generator) use ($action, $periodStart, $periodEnd, &$count) {
            $report = $action->execute($generator, $periodStart, $periodEnd);
            GeneratorHealthReportGenerated::dispatch($report);
            $count++;
        });

        $this->info("تم توليد {$count} تقرير صحة.");

        return self::SUCCESS;
    }
}
