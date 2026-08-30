<?php

namespace App\Http\Requests\Fuel;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tank_level_liters' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'meter_hours' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'reading_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => [
                'file',
                'max:'.config('attachments.max_size_kb'),
                'mimetypes:'.implode(',', config('attachments.allowed_mimes')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tank_level_liters.required' => 'مستوى الخزان مطلوب.',
            'reading_date.required' => 'تاريخ القراءة مطلوب.',
            'reading_date.before_or_equal' => 'تاريخ القراءة لا يمكن أن يكون بالمستقبل.',
        ];
    }
}
