<?php

namespace App\Http\Requests\GeneratorSchedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneratorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('generator_schedule'));
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
