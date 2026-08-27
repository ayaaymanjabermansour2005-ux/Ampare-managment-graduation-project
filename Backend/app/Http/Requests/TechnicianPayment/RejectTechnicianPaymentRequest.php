<?php

namespace App\Http\Requests\TechnicianPayment;

use Illuminate\Foundation\Http\FormRequest;

class RejectTechnicianPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'يجب توضيح سبب رفض الدفعة.',
        ];
    }
}
