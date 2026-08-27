<?php

namespace App\Http\Requests\AiChat;

use Illuminate\Foundation\Http\FormRequest;

class SendAiChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'نص الرسالة مطلوب.',
        ];
    }
}
