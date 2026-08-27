<?php

namespace App\Http\Requests\PaymentMethod;

use App\Enums\Currency;
use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PaymentMethod::class);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'is_default' => ['sometimes', 'boolean'],

            'currency' => [
                Rule::requiredIf(fn () => $this->resolvedType()?->requiresCurrency() ?? false),
                Rule::prohibitedIf(fn () => $this->resolvedType() !== null && ! $this->resolvedType()->requiresCurrency()),
                Rule::enum(Currency::class),
            ],

            'bank_name' => [
                Rule::requiredIf(fn () => $this->resolvedType()?->requiresAccountDetails() ?? false),
                'nullable',
                'string',
                'max:191',
            ],
            'account_name' => [
                Rule::requiredIf(fn () => $this->resolvedType()?->requiresAccountDetails() ?? false),
                'nullable',
                'string',
                'max:191',
            ],
            'account_number' => [
                Rule::requiredIf(fn () => $this->resolvedType()?->requiresAccountDetails() ?? false),
                'nullable',
                'string',
                'max:191',
            ],
        ];
    }

    private function resolvedType(): ?PaymentMethodType
    {
        return PaymentMethodType::tryFrom((string) $this->input('type'));
    }

    public function messages(): array
    {
        return [
            'type.required' => 'يجب اختيار نوع وسيلة الدفع.',
            'type.enum' => 'نوع وسيلة الدفع غير صالح.',

            'currency.required' => 'يجب تحديد عملة هذا الحساب (ILS أو USD).',
            'currency.prohibited' => 'وسيلة الدفع النقدية (Cash) لا تُربط بعملة ثابتة.',
            'currency.enum' => 'العملة يجب أن تكون ILS أو USD.',

            'bank_name.required' => 'اسم البنك مطلوب لهذا النوع من وسائل الدفع.',
            'account_name.required' => 'اسم صاحب الحساب مطلوب.',
            'account_number.required' => 'رقم الحساب مطلوب.',
        ];
    }
}
