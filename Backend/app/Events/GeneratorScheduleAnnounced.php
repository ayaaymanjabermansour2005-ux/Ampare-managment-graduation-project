<?php

namespace App\Events;

use App\Models\GeneratorSchedule;
use Illuminate\Foundation\Events\Dispatchable;

class GeneratorScheduleAnnounced
{
    use Dispatchable;

    public function __construct(public GeneratorSchedule $schedule) {}
}
