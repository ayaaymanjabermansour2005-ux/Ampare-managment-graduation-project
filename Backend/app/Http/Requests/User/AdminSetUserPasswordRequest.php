<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AdminSetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'confirmed',
                Password::min(10)->mixedCase()->numbers()->symbols(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ];
    }
}
