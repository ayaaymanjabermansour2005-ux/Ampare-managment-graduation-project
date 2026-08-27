<?php

namespace App\Http\Requests\User;

use App\Enums\CommissionMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnerCommissionSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commission_mode' => ['required', Rule::enum(CommissionMode::class)],
            'commission_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                Rule::requiredIf(fn() => $this->input('commission_mode') === CommissionMode::Fixed->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'commission_mode.required' => 'يجب تحديد نمط احتساب العمولة.',
            'commission_rate.required_if' => 'نسبة العمولة مطلوبة عند اختيار الوضع الثابت.',
            'commission_rate.max' => 'نسبة العمولة لا يمكن أن تتجاوز 100%.',
        ];
    }
}
