<?php

namespace App\Http\Requests\Technician;

use App\Enums\TechnicianStatus;
use App\Http\Requests\Concerns\NormalizesPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTechnicianRequest extends FormRequest
{
    use NormalizesPhoneNumber;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $technician = $this->route('technician');

        return [
            'status' => ['sometimes', Rule::enum(TechnicianStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($technician?->user_id),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^\+?[0-9]{7,15}$/',
                Rule::unique('users', 'phone')->ignore($technician?->user_id)->whereNull('deleted_at'),
            ],
        ];
    }
}
