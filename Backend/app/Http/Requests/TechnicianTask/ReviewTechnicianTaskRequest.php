<?php

namespace App\Http\Requests\TechnicianTask;

use App\Models\TechnicianTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewTechnicianTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TechnicianTask $task */
        $task = $this->route('technician_task');

        return $this->user()->can('review', $task);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
            'admin_override_reason' => [
                ($this->user()?->isAdmin() ?? false) ? 'required' : 'prohibited',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'يجب كتابة سبب الرفض.',
            'admin_override_reason.required' => 'يجب توثيق سبب تدخل الأدمن بدل مالك المولد.',
        ];
    }
}
