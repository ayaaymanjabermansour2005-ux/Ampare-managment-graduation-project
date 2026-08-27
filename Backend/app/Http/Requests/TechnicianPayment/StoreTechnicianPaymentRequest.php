<?php

namespace App\Http\Requests\TechnicianPayment;

use App\Enums\Currency;
use App\Models\PaymentMethod;
use App\Models\Technician;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTechnicianPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician_id' => ['required', 'integer', 'exists:technicians,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', Rule::enum(Currency::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.required' => 'يجب تحديد الفني.',
            'technician_id.exists' => 'الفني المحدَّد غير موجود.',
            'amount.required' => 'يجب إدخال مبلغ الدفعة.',
            'amount.min' => 'يجب أن تكون قيمة الدفعة أكبر من صفر.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();

            if (! $user || ! $this->filled('technician_id')) {
                return;
            }

            $technician = Technician::find($this->input('technician_id'));

            if ($technician && $technician->owner_id !== $user->id) {
                $validator->errors()->add('technician_id', 'هذا الفني ليس تابعًا لك.');
            }

            if ($this->filled('payment_method_id')) {
                $method = PaymentMethod::find($this->input('payment_method_id'));

                if ($method && $method->user_id !== $user->id) {
                    $validator->errors()->add('payment_method_id', 'وسيلة الدفع المحددة لا تخصك.');
                }
            }
        });
    }
}
