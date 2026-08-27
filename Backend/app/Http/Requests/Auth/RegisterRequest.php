<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesPhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use NormalizesPhoneNumber;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],

            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],

            'phone' => [
                'nullable',
                'string',
                'regex:/^\+?[0-9]{7,15}$/',
                Rule::unique('users', 'phone')->whereNull('deleted_at'),
            ],

            'neighborhood_id' => ['required', 'integer', 'exists:neighborhoods,id'],
            'address' => ['required', 'string', 'max:255'],

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
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم مسبقاً.',
            'phone.regex' => 'صيغة رقم الهاتف غير صحيحة.',
            'phone.unique' => 'رقم الهاتف هذا مستخدم مسبقًا من قِبل حساب آخر.',
            'neighborhood_id.required' => 'يجب تحديد الحي.',
            'neighborhood_id.exists' => 'الحي المحدد غير موجود.',
            'address.required' => 'العنوان مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.uncompromised' => 'كلمة المرور هذه معروفة ضمن تسريبات بيانات سابقة، اختر كلمة مرور أخرى.',
        ];
    }
}
