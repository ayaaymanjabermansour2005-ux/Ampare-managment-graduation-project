<?php

namespace App\Http\Requests\SubscriberMeter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriberMeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meter_number' => ['required', 'string', 'max:50', Rule::unique('subscriber_meters', 'meter_number')->whereNull('deleted_at')],
            'property_label' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'meter_number.required' => 'رقم العداد مطلوب.',
            'meter_number.unique' => 'رقم العداد هذا مسجّل مسبقاً.',
        ];
    }
}
