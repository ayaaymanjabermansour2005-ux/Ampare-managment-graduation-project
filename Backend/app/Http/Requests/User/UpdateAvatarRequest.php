<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'يجب اختيار صورة.',
            'avatar.image' => 'الملف يجب أن يكون صورة.',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
        ];
    }
}
