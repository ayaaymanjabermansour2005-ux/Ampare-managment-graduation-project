<?php

namespace App\Actions\TechnicianTask;

use App\Enums\TechnicianTaskStatus;
use App\Models\TechnicianTask;
use App\Support\Eloquent\FreshOrFail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StartTechnicianTaskAction
{
    public function execute(TechnicianTask $task): TechnicianTask
    {
        return DB::transaction(function () use ($task) {
            $task = TechnicianTask::lockForUpdate()->findOrFail($task->id);

            if (! in_array($task->status, [
                TechnicianTaskStatus::Assigned,
                TechnicianTaskStatus::OnTheWay,
                TechnicianTaskStatus::WaitingParts,
            ], true)) {
                throw ValidationException::withMessages([
                    'task' => ['لا يمكن بدء التنفيذ من الحالة الحالية.'],
                ]);
            }

            $task->forceFill([
                'status' => TechnicianTaskStatus::InProgress,
                'started_at' => $task->started_at ?? now(),
            ])->save();

            return FreshOrFail::reload($task);
        });
    }
}
