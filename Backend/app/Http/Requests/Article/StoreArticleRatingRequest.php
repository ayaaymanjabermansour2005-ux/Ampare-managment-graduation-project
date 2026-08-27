<?php

namespace App\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'يجب اختيار تقييم.',
            'rating.between' => 'التقييم يجب أن يكون بين 1 و5 نجوم.',
        ];
    }
}
