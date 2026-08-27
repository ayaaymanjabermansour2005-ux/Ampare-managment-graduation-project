<?php

namespace App\Actions\TechnicianTask;

use App\Enums\TechnicianTaskStatus;
use App\Events\TechnicianTaskSubmitted;
use App\Models\TechnicianTask;
use App\Support\TechnicianTask\FaultTaskSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubmitTechnicianTaskAction
{
    public function __construct(
        private readonly FaultTaskSynchronizer $faultSynchronizer,
    ) {}

    public function execute(TechnicianTask $task, string $completionNotes): TechnicianTask
    {
        return DB::transaction(function () use ($task, $completionNotes) {
            $task = TechnicianTask::lockForUpdate()->findOrFail($task->id);

            if (! $task->isInProgress()) {
                throw ValidationException::withMessages([
                    'task' => ['لا يمكن إرسال المهمة للمراجعة إلا أثناء التنفيذ.'],
                ]);
            }

            $task->forceFill([
                'status' => TechnicianTaskStatus::Submitted,
                'completion_notes' => $completionNotes,
                'submitted_at' => now(),
            ])->save();

            $fresh = $task->fresh(['generator.owner', 'technician.user']);

            $this->faultSynchronizer->onSubmit($fresh);

            TechnicianTaskSubmitted::dispatch($fresh);

            return $fresh;
        });
    }
}
