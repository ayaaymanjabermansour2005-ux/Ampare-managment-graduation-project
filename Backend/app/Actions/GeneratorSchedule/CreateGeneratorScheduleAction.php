<?php

namespace App\Actions\GeneratorSchedule;

use App\DTOs\GeneratorSchedule\CreateGeneratorScheduleData;
use App\Events\GeneratorScheduleAnnounced;
use App\Models\Generator;
use App\Models\GeneratorSchedule;
use App\Models\User;

final class CreateGeneratorScheduleAction
{
    public function execute(Generator $generator, CreateGeneratorScheduleData $data, User $user): GeneratorSchedule
    {
        $schedule = GeneratorSchedule::create([
            'generator_id' => $generator->id,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
            'note' => $data->note,
            'created_by' => $user->id,
        ]);

        GeneratorScheduleAnnounced::dispatch($schedule);

        return $schedule->fresh(['generator', 'creator']);
    }
}
