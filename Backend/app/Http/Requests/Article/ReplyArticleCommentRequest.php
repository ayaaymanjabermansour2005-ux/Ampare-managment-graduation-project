<?php

namespace App\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

class ReplyArticleCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_reply' => ['required', 'string', 'min:2', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_reply.required' => 'نص الرد مطلوب.',
            'admin_reply.max' => 'الرد طويل جدًا (الحد الأقصى 1000 حرف).',
        ];
    }
}
