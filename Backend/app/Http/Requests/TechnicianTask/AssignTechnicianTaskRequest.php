<?php

namespace App\Http\Requests\TechnicianTask;

use App\Models\Technician;
use App\Models\TechnicianTask;
use App\Support\TechnicianTask\TechnicianEligibilityChecker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class AssignTechnicianTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TechnicianTask $task */
        $task = $this->route('technician_task');

        return $this->user()->can('assign', $task);
    }

    public function rules(): array
    {
        /** @var TechnicianTask $task */
        $task = $this->route('technician_task');
        $user = $this->user();

        return [
            'technician_id' => [
                'required',
                'integer',
                'exists:technicians,id',
                function ($attribute, $value, $fail) use ($task, $user) {
                    $technician = Technician::find($value);

                    if (! $technician || ! $technician->isActive()) {
                        $fail('الفني المحدد غير متاح حاليًا.');

                        return;
                    }

                    if (! $user->isAdmin() && $technician->owner_id !== $user->id) {
                        $fail('لا يمكنك تعيين فني لا يخصك.');

                        return;
                    }

                    try {
                        app(TechnicianEligibilityChecker::class)->assertEligible($technician, $task->generator);
                    } catch (ValidationException $exception) {
                        $fail(collect($exception->errors())->collapse()->first());
                    }
                },
            ],
        ];
    }
}
