<?php

namespace App\Http\Requests\PlatformCommission;

use App\Enums\PlatformCommissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformCommissionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('platform_commission'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                PlatformCommissionStatus::Paid->value,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'حالة غير صالحة — يمكن فقط اعتماد التحويل (paid).',
        ];
    }
}
