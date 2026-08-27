<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DeleteOwnAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password:web'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'يجب إدخال كلمة المرور لتأكيد حذف الحساب.',
            'password.current_password' => 'كلمة المرور غير صحيحة.',
        ];
    }
}
