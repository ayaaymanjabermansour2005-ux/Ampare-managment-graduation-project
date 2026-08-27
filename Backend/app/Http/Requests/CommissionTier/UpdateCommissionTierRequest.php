<?php

namespace App\Http\Requests\CommissionTier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommissionTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_generators_count' => ['sometimes', 'integer', 'min:0'],
            'max_generators_count' => ['sometimes', 'nullable', 'integer', 'gte:min_generators_count'],
            'commission_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
