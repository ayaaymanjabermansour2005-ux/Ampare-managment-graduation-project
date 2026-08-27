<?php

namespace App\Http\Requests\MeterReading;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeterReadingAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'يجب اختيار صورة أو ملف.',
            'file.mimes' => 'الصيغ المسموحة: jpg, jpeg, png, pdf.',
        ];
    }
}
