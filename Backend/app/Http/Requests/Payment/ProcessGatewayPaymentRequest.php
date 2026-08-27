<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessGatewayPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'card_number' => ['required', 'string', 'regex:/^[\d\s]{13,23}$/'],
            'card_holder_name' => ['required', 'string', 'max:100'],
            'expiry_month' => ['required', 'digits_between:1,2', 'integer', 'min:1', 'max:12'],
            'expiry_year' => ['required', 'digits_between:2,4'],
            'cvv' => ['required', 'digits_between:3,4'],
        ];
    }

    public function messages(): array
    {
        return [
            'card_number.regex' => 'رقم البطاقة غير صحيح.',
            'invoice_id.exists' => 'الفاتورة غير موجودة.',
        ];
    }
}
