<?php

namespace App\Http\Requests\SubscriptionMeterTransfer;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionMeterTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'to_subscriber_meter_id' => ['required', 'integer', 'exists:subscriber_meters,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_id.required' => 'يجب تحديد الاشتراك المطلوب نقل عدّاده.',
            'subscription_id.exists' => 'الاشتراك المحدَّد غير موجود.',
            'to_subscriber_meter_id.required' => 'يجب تحديد العداد الجديد.',
            'to_subscriber_meter_id.exists' => 'العداد المحدَّد غير موجود.',
        ];
    }
}
