<?php

namespace App\Actions\TechnicianTask;

use App\Enums\TechnicianTaskStatus;
use App\Models\TechnicianTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MarkTaskWaitingPartsAction
{
    public function execute(TechnicianTask $task): TechnicianTask
    {
        return DB::transaction(function () use ($task) {
            $task = TechnicianTask::lockForUpdate()->findOrFail($task->id);

            if ($task->status !== TechnicianTaskStatus::InProgress) {
                throw ValidationException::withMessages([
                    'task' => ['لا يمكن الانتقال لحالة "بانتظار قطع غيار" إلا أثناء التنفيذ.'],
                ]);
            }

            $task->forceFill(['status' => TechnicianTaskStatus::WaitingParts])->save();

            return $task->fresh();
        });
    }
}
