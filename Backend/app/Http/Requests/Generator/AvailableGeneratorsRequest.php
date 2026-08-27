<?php

namespace App\Http\Requests\Generator;

use Illuminate\Foundation\Http\FormRequest;

class AvailableGeneratorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscriber_meter_id' => ['nullable', 'integer', 'exists:subscriber_meters,id'],
            'schedule' => ['nullable', 'in:day,night,24h,custom'],
            'service_start_time' => ['required_if:schedule,custom', 'date_format:H:i'],
            'service_end_time' => ['required_if:schedule,custom', 'date_format:H:i', 'after:service_start_time'],
            'requested_capacity_kw' => ['nullable', 'numeric', 'min:0.1'],
        ];
    }
}
