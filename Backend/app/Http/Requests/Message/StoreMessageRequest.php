<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_text' => ['required_without:attachments', 'nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    public function messages(): array
    {
        return [
            'message_text.required_without' => 'يجب كتابة نص أو إرفاق ملف واحد على الأقل.',
        ];
    }
}
