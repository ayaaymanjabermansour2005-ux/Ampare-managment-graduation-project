<?php

namespace App\Actions\TechnicianTask;

use App\DTOs\TechnicianTask\CreateTechnicianTaskData;
use App\Enums\TechnicianTaskStatus;
use App\Events\TechnicianTaskAssigned;
use App\Models\Generator;
use App\Models\Technician;
use App\Models\TechnicianTask;
use App\Models\User;
use App\Support\TechnicianTask\TechnicianEligibilityChecker;
use Illuminate\Support\Facades\DB;

final class CreateTechnicianTaskAction
{
    public function __construct(
        private readonly TechnicianEligibilityChecker $eligibilityChecker,
    ) {}

    public function execute(CreateTechnicianTaskData $data, User $user): TechnicianTask
    {
        $generator = Generator::findOrFail($data->generatorId);

        $technician = null;
        if ($data->technicianId !== null) {
            $technician = Technician::findOrFail($data->technicianId);
            $this->eligibilityChecker->assertEligible($technician, $generator);
        }

        return DB::transaction(function () use ($data, $generator, $technician, $user) {
            $task = new TechnicianTask;

            $task->forceFill([
                'generator_id' => $generator->id,
                'taskable_type' => $data->taskableType,
                'taskable_id' => $data->taskableId,
                'type' => $data->type,
                'instructions' => $data->instructions,
                'technician_id' => $technician?->id,
                'requested_by' => $user->id,
                'assigned_by' => $technician ? $user->id : null,
                'status' => $technician
                    ? TechnicianTaskStatus::Assigned->value
                    : TechnicianTaskStatus::Pending->value,
                'assigned_at' => $technician ? now() : null,
            ])->save();

            if ($task->status === TechnicianTaskStatus::Assigned) {
                TechnicianTaskAssigned::dispatch($task->fresh(['generator', 'technician.user']));
            }

            return $task->fresh(['generator', 'technician.user', 'requestedBy']);
        });
    }
}
