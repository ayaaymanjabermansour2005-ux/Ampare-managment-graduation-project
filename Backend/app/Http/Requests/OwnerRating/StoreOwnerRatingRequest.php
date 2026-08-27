<?php

namespace App\Http\Requests\OwnerRating;

use Illuminate\Foundation\Http\FormRequest;

class StoreOwnerRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('rateOwner', $this->route('subscription'));
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
            'rating.min' => 'أقل تقييم هو نجمة واحدة.',
            'rating.max' => 'أعلى تقييم هو ٥ نجوم.',
        ];
    }
}
