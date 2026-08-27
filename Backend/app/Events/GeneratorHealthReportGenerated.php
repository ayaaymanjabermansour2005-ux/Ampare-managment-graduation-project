<?php

namespace App\Events;

use App\Models\GeneratorHealthReport;
use Illuminate\Foundation\Events\Dispatchable;

class GeneratorHealthReportGenerated
{
    use Dispatchable;

    public function __construct(public GeneratorHealthReport $report) {}
}
