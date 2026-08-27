<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:web'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
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
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور الجديدة غير مطابق.',
            'password.different' => 'كلمة المرور الجديدة يجب أن تختلف عن الحالية.',
            'password.uncompromised' => 'كلمة المرور هذه معروفة ضمن تسريبات بيانات سابقة، اختر كلمة مرور أخرى.',
        ];
    }
}
