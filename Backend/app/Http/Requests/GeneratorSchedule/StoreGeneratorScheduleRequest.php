<?php

namespace App\Http\Requests\GeneratorSchedule;

use App\Models\Generator;
use Illuminate\Foundation\Http\FormRequest;

class StoreGeneratorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Generator $generator */
        $generator = $this->route('generator');

        return $this->user()->can('manageSchedules', $generator);
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'ends_at.after' => 'وقت الانتهاء يجب أن يكون بعد وقت البداية.',
        ];
    }
}
