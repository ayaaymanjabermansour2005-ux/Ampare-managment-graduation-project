<?php

namespace App\Http\Requests\SubscriptionMeterTransfer;

use Illuminate\Foundation\Http\FormRequest;

class RejectSubscriptionMeterTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'يجب توضيح سبب رفض طلب نقل العداد.',
        ];
    }
}
