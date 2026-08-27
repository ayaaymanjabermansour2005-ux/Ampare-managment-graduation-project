<?php

namespace App\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'comment' => ['required', 'string', 'min:3', 'max:1000'],

            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'comment.required' => 'التعليق مطلوب.',
            'comment.min' => 'التعليق قصير جدًا.',
            'comment.max' => 'التعليق طويل جدًا (الحد الأقصى 1000 حرف).',
        ];
    }
}
