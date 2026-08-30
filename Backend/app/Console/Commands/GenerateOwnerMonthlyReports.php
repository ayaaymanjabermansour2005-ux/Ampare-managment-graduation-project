<?php

namespace App\Console\Commands;

use App\Jobs\SendOwnerMonthlyReportEmail;
use App\Models\Generator;
use Illuminate\Console\Command;

class GenerateOwnerMonthlyReports extends Command
{
    protected $signature = 'reports:owner-monthly {--month=}';

    protected $description = 'يولّد ويرسل التقرير الشهري لكل مالك مولد نشط';

    public function handle(): int
    {
        $month = $this->option('month')
            ? now()->parse($this->option('month'))
            : now()->subMonthNoOverflow();

        $ownerIds = Generator::query()->distinct()->pluck('owner_id');

        foreach ($ownerIds as $ownerId) {
            SendOwnerMonthlyReportEmail::dispatch($ownerId, $month->format('Y-m-01'));
        }

        $this->info("تم جدولة {$ownerIds->count()} تقرير شهري.");

        return self::SUCCESS;
    }
}
