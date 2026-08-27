<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class NeedsCorrectionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Payment $payment */
        $payment = $this->route('payment');

        return $this->user()->can('needsCorrection', $payment);
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'يجب توضيح سبب طلب التعديل (مثلًا: الصورة غير واضحة).',
        ];
    }
}
