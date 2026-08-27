<?php

namespace App\Actions\TechnicianTask;

use App\Enums\TechnicianTaskStatus;
use App\Events\TechnicianTaskAssigned;
use App\Models\Technician;
use App\Models\TechnicianTask;
use App\Models\User;
use App\Support\TechnicianTask\TechnicianEligibilityChecker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssignTechnicianTaskAction
{
    public function __construct(
        private readonly TechnicianEligibilityChecker $eligibilityChecker,
    ) {}

    public function execute(TechnicianTask $task, int $technicianId, User $user): TechnicianTask
    {
        $technician = Technician::findOrFail($technicianId);

        $this->eligibilityChecker->assertEligible($technician, $task->generator);

        return DB::transaction(function () use ($task, $technician, $user) {
            $task = TechnicianTask::lockForUpdate()->findOrFail($task->id);

            if ($task->isClosed()) {
                throw ValidationException::withMessages([
                    'task' => ['لا يمكن تعيين فني على مهمة مغلقة.'],
                ]);
            }

            $task->forceFill([
                'technician_id' => $technician->id,
                'assigned_by' => $user->id,
                'status' => TechnicianTaskStatus::Assigned,
                'assigned_at' => now(),
            ])->save();

            $fresh = $task->fresh(['generator', 'technician.user']);
            TechnicianTaskAssigned::dispatch($fresh);

            return $fresh;
        });
    }
}
