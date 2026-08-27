<?php

namespace App\Support\TechnicianTask;

use App\Enums\FaultStatus;
use App\Models\Fault;
use App\Models\TechnicianTask;
use App\Models\User;

class FaultTaskSynchronizer
{
    public function onSubmit(TechnicianTask $task): void
    {
        if (! $task->taskable instanceof Fault) {
            return;
        }

        $task->taskable->update([
            'status' => FaultStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    public function onReview(TechnicianTask $task, bool $approved, User $user): void
    {
        if (! $task->taskable instanceof Fault) {
            return;
        }

        $fault = $task->taskable;

        if ($approved) {
            $fault->update([
                'status' => FaultStatus::Closed,
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);
        } else {
            $fault->update([
                'status' => FaultStatus::InRepair,
                'resolved_at' => null,
            ]);
        }
    }
}
