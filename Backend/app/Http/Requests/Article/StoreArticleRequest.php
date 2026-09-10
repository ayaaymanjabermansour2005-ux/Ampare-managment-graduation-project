<?php

namespace App\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // التفويض الفعلي صار عبر permission middleware بالراوت + ArticlePolicy
        // بالكنترولر (create/update) — بعد إضافة صلاحيات articles.* الدقيقة.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],

            'title_en' => ['required', 'string', 'max:255'],
            'excerpt_en' => ['nullable', 'string', 'max:500'],
            'content_en' => ['required', 'string'],

            'cover_image_url' => ['nullable', 'url'],
            'is_published' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title_en.required' => 'العنوان بالإنجليزي مطلوب — المقال لازم يكون بلغتين.',
            'content_en.required' => 'محتوى المقال بالإنجليزي مطلوب — المقال لازم يكون بلغتين.',
        ];
    }
}
