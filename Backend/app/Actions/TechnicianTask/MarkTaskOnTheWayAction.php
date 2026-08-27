<?php

namespace App\Actions\TechnicianTask;

use App\Enums\TechnicianTaskStatus;
use App\Models\TechnicianTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MarkTaskOnTheWayAction
{
    public function execute(TechnicianTask $task): TechnicianTask
    {
        return DB::transaction(function () use ($task) {
            $task = TechnicianTask::lockForUpdate()->findOrFail($task->id);

            if ($task->status !== TechnicianTaskStatus::Assigned) {
                throw ValidationException::withMessages([
                    'task' => ['لا يمكن الانتقال لحالة "في الطريق" إلا من حالة "معيّن".'],
                ]);
            }

            $task->forceFill(['status' => TechnicianTaskStatus::OnTheWay])->save();

            return $task->fresh();
        });
    }
}
