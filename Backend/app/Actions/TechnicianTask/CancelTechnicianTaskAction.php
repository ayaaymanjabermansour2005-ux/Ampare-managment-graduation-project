<?php

namespace App\Actions\TechnicianTask;

use App\Enums\TechnicianTaskStatus;
use App\Models\TechnicianTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelTechnicianTaskAction
{
    public function execute(TechnicianTask $task): TechnicianTask
    {
        return DB::transaction(function () use ($task) {
            $task = TechnicianTask::lockForUpdate()->findOrFail($task->id);

            if ($task->isClosed() || $task->isSubmitted()) {
                throw ValidationException::withMessages([
                    'task' => ['لا يمكن إلغاء مهمة مغلقة أو بانتظار المراجعة.'],
                ]);
            }

            $task->forceFill(['status' => TechnicianTaskStatus::Cancelled])->save();

            return $task->fresh();
        });
    }
}
