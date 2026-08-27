<?php

namespace App\Http\Requests\SubscriptionServiceRequest;

use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSubscriptionServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:1000'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'fee_currency' => ['required_with:fee_amount', Rule::enum(Currency::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'قرار المراجعة مطلوب (approved أو rejected).',
            'fee_currency.required_with' => 'عملة الرسوم مطلوبة عند تحديد مبلغ الرسوم.',
        ];
    }
}
