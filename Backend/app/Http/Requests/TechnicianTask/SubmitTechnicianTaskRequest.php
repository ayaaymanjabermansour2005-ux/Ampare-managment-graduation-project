<?php

namespace App\Http\Requests\TechnicianTask;

use App\Models\TechnicianTask;
use Illuminate\Foundation\Http\FormRequest;

class SubmitTechnicianTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TechnicianTask $task */
        $task = $this->route('technician_task');

        return $this->user()->can('submit', $task);
    }

    public function rules(): array
    {
        return [
            'completion_notes' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'completion_notes.required' => 'يجب توضيح ما تم إنجازه قبل الإرسال للمراجعة.',
        ];
    }
}
