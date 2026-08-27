<?php

namespace App\Actions\TechnicianTask;

use App\DTOs\TechnicianTask\ReviewTechnicianTaskData;
use App\Enums\ReviewerRole;
use App\Enums\TechnicianTaskStatus;
use App\Events\TechnicianTaskApproved;
use App\Events\TechnicianTaskRejected;
use App\Models\TechnicianTask;
use App\Models\User;
use App\Support\TechnicianTask\FaultTaskSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReviewTechnicianTaskAction
{
    public function __construct(
        private readonly FaultTaskSynchronizer $faultSynchronizer,
    ) {}

    public function execute(TechnicianTask $task, ReviewTechnicianTaskData $data, User $user): TechnicianTask
    {
        $approved = $data->decision === 'approved';
        $reviewerRole = $user->isAdmin() ? ReviewerRole::Admin : ReviewerRole::Owner;

        return DB::transaction(function () use ($task, $approved, $data, $user, $reviewerRole) {
            $task = TechnicianTask::lockForUpdate()->findOrFail($task->id);

            if (! $task->isSubmitted()) {
                throw ValidationException::withMessages([
                    'task' => ['هذه المهمة ليست بانتظار المراجعة.'],
                ]);
            }

            $task->forceFill([
                'status' => $approved ? TechnicianTaskStatus::Approved : TechnicianTaskStatus::Rejected,
                'rejection_reason' => $approved ? null : $data->rejectionReason,
                'reviewed_by' => $user->id,
                'reviewer_role' => $reviewerRole,
                'admin_override_reason' => $reviewerRole === ReviewerRole::Admin ? $data->adminOverrideReason : null,
                'reviewed_at' => now(),
            ])->save();

            $fresh = $task->fresh(['generator', 'technician.user']);

            $this->faultSynchronizer->onReview($fresh, $approved, $user);

            $approved
                ? TechnicianTaskApproved::dispatch($fresh)
                : TechnicianTaskRejected::dispatch($fresh);

            return $fresh;
        });
    }
}
