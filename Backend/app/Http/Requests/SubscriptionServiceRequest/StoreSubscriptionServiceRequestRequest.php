<?php

namespace App\Http\Requests\SubscriptionServiceRequest;

use App\Enums\ServiceRequestEventType;
use App\Enums\ServiceRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'request_type' => ['required', Rule::enum(ServiceRequestType::class)],
            'event_type' => [
                Rule::requiredIf(fn () => $this->input('request_type') === 'event'),
                Rule::excludeIf(fn () => $this->input('request_type') !== 'event'),
                Rule::enum(ServiceRequestEventType::class),
            ],
            'description' => ['required', 'string', 'max:1000'],
            'extra_capacity_kw' => ['nullable', 'numeric', 'min:0.1', 'max:9999.99'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_type.required_if' => 'نوع المناسبة مطلوب عند اختيار "مناسبة" كنوع الطلب.',
            'starts_at.after' => 'موعد البداية يجب أن يكون بالمستقبل.',
            'ends_at.after' => 'موعد النهاية يجب أن يكون بعد موعد البداية.',
        ];
    }
}
