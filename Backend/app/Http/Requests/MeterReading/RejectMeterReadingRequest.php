<?php

namespace App\Http\Requests\MeterReading;

use Illuminate\Foundation\Http\FormRequest;

class RejectMeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'يجب توضيح سبب رفض القراءة.',
        ];
    }
}
