<?php

namespace App\Http\Requests\PaymentMethod;

use App\Enums\Currency;
use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('payment_method'));
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(PaymentMethodType::class)],
            'is_default' => ['sometimes', 'boolean'],

            'currency' => [
                Rule::requiredIf(fn () => $this->has('type') && $this->resolvedType()?->requiresCurrency()),
                Rule::prohibitedIf(fn () => $this->resolvedType() !== null && ! $this->resolvedType()->requiresCurrency()),
                Rule::enum(Currency::class),
            ],

            'bank_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'account_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:191'],
        ];
    }

    private function resolvedType(): ?PaymentMethodType
    {
        if ($this->has('type')) {
            return PaymentMethodType::tryFrom((string) $this->input('type'));
        }

        /** @var PaymentMethod|null $paymentMethod */
        $paymentMethod = $this->route('payment_method');

        return $paymentMethod?->type;
    }

    public function messages(): array
    {
        return [
            'type.enum' => 'نوع وسيلة الدفع غير صالح.',

            'currency.required' => 'يجب تحديد عملة هذا الحساب (ILS أو USD).',
            'currency.prohibited' => 'وسيلة الدفع النقدية (Cash) لا تُربط بعملة ثابتة.',
            'currency.enum' => 'العملة يجب أن تكون ILS أو USD.',
        ];
    }
}
