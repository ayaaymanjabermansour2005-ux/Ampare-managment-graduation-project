<?php

namespace App\Http\Requests\SubscriberMeter;

use App\Enums\SubscriberMeterStatus;
use App\Models\SubscriberMeter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriberMeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var SubscriberMeter|null $meter */
        $meter = $this->route('subscriber_meter');

        return [
            'meter_number' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('subscriber_meters', 'meter_number')->ignore($meter?->id)->whereNull('deleted_at')],
            'property_label' => ['nullable', 'string', 'max:150'],
            'status' => ['sometimes', Rule::enum(SubscriberMeterStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'حالة العداد غير صالحة.',
        ];
    }
}
