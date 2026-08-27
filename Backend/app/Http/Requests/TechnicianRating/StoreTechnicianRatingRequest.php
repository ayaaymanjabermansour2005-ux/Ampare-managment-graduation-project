<?php

namespace App\Http\Requests\TechnicianRating;

use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'التقييم مطلوب.',
            'rating.min' => 'التقييم يجب أن يكون بين ١ و٥.',
            'rating.max' => 'التقييم يجب أن يكون بين ١ و٥.',
        ];
    }
}
