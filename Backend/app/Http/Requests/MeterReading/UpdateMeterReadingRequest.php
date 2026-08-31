<?php

namespace App\Http\Requests\MeterReading;

use App\Models\MeterReading;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('meter_reading'));
    }

    public function rules(): array
    {
        // The `{meter_reading}` route parameter is implicitly model-bound
        // (see MeterReadingController::update()'s `MeterReading $meterReading`
        // type-hint) — it is always a real MeterReading by the time rules()
        // runs, but Illuminate\Http\Request::route() itself is typed
        // `object|string|null`, which PHPStan takes at face value.
        $meterReading = $this->route('meter_reading');

        return [
            'current_reading' => [
                'required',
                'numeric',
                'min:'.($meterReading instanceof MeterReading ? $meterReading->previous_reading : 0),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_reading.required' => 'قيمة القراءة الحالية مطلوبة.',
            'current_reading.min' => 'لا يمكن أن تكون القراءة الحالية أقل من القراءة السابقة (:min).',
        ];
    }
}
