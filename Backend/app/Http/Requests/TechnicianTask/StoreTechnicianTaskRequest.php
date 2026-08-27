<?php

namespace App\Http\Requests\TechnicianTask;

use App\Enums\TechnicianTaskType;
use App\Models\Generator;
use App\Models\Technician;
use App\Models\TechnicianTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TechnicianTask::class);
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'generator_id' => [
                'required',
                'integer',
                'exists:generators,id',
                function ($attribute, $value, $fail) use ($user) {
                    if ($user->isAdmin()) {
                        return;
                    }

                    $generator = Generator::find($value);

                    if (! $generator || $generator->owner_id !== $user->id) {
                        $fail('هذا المولد ليس ضمن مولداتك.');
                    }
                },
            ],

            'type' => ['required', Rule::enum(TechnicianTaskType::class)],

            'instructions' => ['nullable', 'string', 'max:2000'],

            'technician_id' => [
                'nullable',
                'integer',
                Rule::exists('technicians', 'id')->where(fn ($q) => $q->whereNull('deleted_at')),
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $technician = Technician::find($value);

                    if (! $technician || ! $technician->isActive()) {
                        $fail('الفني المحدد غير متاح حاليًا.');
                    }
                },
            ],

            'taskable_type' => ['nullable', 'string', Rule::in(['subscription', 'fault'])],
            'taskable_id' => ['required_with:taskable_type', 'nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'generator_id.exists' => 'المولد المحدد غير موجود.',
            'type.enum' => 'نوع المهمة غير صالح.',
        ];
    }
}
