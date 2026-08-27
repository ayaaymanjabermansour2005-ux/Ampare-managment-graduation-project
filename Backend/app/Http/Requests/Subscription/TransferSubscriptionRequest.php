<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class TransferSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'generator_id' => ['required', 'integer', 'exists:generators,id'],
        ];
    }

    public function messages(): array
    {
        return ['generator_id.required' => 'يجب تحديد المولد الجديد.'];
    }
}
