<?php

namespace App\Http\Requests\Fault;

use Illuminate\Foundation\Http\FormRequest;

class VerifyFaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_valid' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_valid.required' => 'يجب تحديد ما إذا كان البلاغ صحيحًا.',
        ];
    }
}
