<?php

namespace App\Http\Requests\Subscription;

use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                SubscriptionStatus::Active->value,
                SubscriptionStatus::Suspended->value,
                SubscriptionStatus::Cancelled->value,
                SubscriptionStatus::Rejected->value,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'حالة غير صالحة.',
        ];
    }
}
