<?php

namespace App\Actions\TechnicianTask;

use App\DTOs\TechnicianRating\CreateTechnicianRatingData;
use App\Models\TechnicianRating;
use App\Models\TechnicianTask;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class RateTechnicianTaskAction
{
    public function execute(TechnicianTask $task, CreateTechnicianRatingData $data, User $user): TechnicianRating
    {
        if ($task->rating()->exists()) {
            throw ValidationException::withMessages([
                'task' => ['تم تقييم هذا أمر الشغل مسبقًا.'],
            ]);
        }

        $rating = TechnicianRating::create([
            'technician_task_id' => $task->id,
            'technician_id' => $task->technician_id,
            'rated_by' => $user->id,
            'rating' => $data->rating,
            'comment' => $data->comment,
        ]);

        return $rating->fresh(['technician', 'rater']);
    }
}
