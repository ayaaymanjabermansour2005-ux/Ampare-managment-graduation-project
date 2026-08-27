<?php

namespace App\Http\Requests\User;

use App\Http\Requests\Concerns\NormalizesPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminCreateSubscriberRequest extends FormRequest
{
    use NormalizesPhoneNumber;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+?[0-9]{7,15}$/',
                Rule::unique('users', 'phone')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
            'address' => ['nullable', 'string', 'max:255'],
            'neighborhood_id' => ['nullable', 'integer', 'exists:neighborhoods,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم مسبقاً.',
            'phone.regex' => 'صيغة رقم الهاتف غير صحيحة.',
            'phone.unique' => 'رقم الهاتف هذا مستخدم مسبقًا.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ];
    }
}
