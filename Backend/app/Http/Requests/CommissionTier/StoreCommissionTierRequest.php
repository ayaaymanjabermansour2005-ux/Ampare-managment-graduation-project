<?php

namespace App\Http\Requests\CommissionTier;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommissionTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_generators_count' => ['required', 'integer', 'min:0'],
            'max_generators_count' => ['nullable', 'integer', 'gte:min_generators_count'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
