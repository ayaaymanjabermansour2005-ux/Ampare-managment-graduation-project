<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => [
                'required',
                'confirmed',
                $this->passwordRule(),
            ],
        ];
    }

    private function passwordRule(): Password
    {
        $rule = Password::min(10)->mixedCase()->numbers()->symbols();

        return app()->environment('testing') ? $rule : $rule->uncompromised();
    }

    public function messages(): array
    {
        return [
            'token.required' => 'رمز إعادة التعيين مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.uncompromised' => 'كلمة المرور هذه معروفة ضمن تسريبات بيانات سابقة، اختر كلمة مرور أخرى.',
        ];
    }
}
