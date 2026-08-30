<?php

namespace App\Http\Requests\Fuel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFuelPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'liters' => ['required', 'numeric', 'min:0.1', 'max:99999.99'],
            'cost_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['ILS', 'USD'])],
            'purchased_at' => ['required', 'date', 'before_or_equal:today'],
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
            'liters.required' => 'كمية اللترات مطلوبة.',
            'cost_amount.required' => 'مبلغ الشراء مطلوب.',
            'purchased_at.before_or_equal' => 'تاريخ الشراء لا يمكن أن يكون بالمستقبل.',
        ];
    }
}
