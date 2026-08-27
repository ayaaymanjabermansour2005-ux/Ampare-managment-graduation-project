<?php

namespace App\Http\Requests\Generator;

use Illuminate\Foundation\Http\FormRequest;

class RejectGeneratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'يجب توضيح سبب رفض الاعتماد.',
        ];
    }
}
