<?php

namespace App\Http\Requests\GeneratorDiagnostic;

use App\Models\Generator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeneratorDiagnosticReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Generator $generator */
        $generator = $this->route('generator');

        return $this->user()->can('record', $generator);
    }

    public function rules(): array
    {
        return [
            'operating_hours' => ['required', 'numeric', 'min:0'],
            'temperature_celsius' => ['nullable', 'numeric', 'min:-40', 'max:200'],
            'oil_level_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'load_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'voltage' => ['nullable', 'numeric', 'min:0'],
            'frequency_hz' => ['nullable', 'numeric', 'min:0'],
            'smoke_level' => ['nullable', Rule::in(['none', 'light', 'heavy'])],
            'vibration_level' => ['nullable', Rule::in(['normal', 'abnormal'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'reading_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'operating_hours.required' => 'عدد ساعات التشغيل مطلوب.',
            'reading_date.required' => 'تاريخ القراءة مطلوب.',
        ];
    }
}
