<?php

namespace App\Http\Requests\Auth;

use App\Services\PhoneNumberNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $login = trim((string) $this->input('login', ''));

        if ($login !== '' && filter_var($login, FILTER_VALIDATE_EMAIL) === false) {
            $this->merge([
                'login' => app(PhoneNumberNormalizer::class)->normalize($login),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                    $isPhone = preg_match('/^\+?[0-9]{7,15}$/', $value) === 1;

                    if (! $isEmail && ! $isPhone) {
                        $fail('يرجى إدخال بريد إلكتروني أو رقم هاتف صحيح.');
                    }
                },
            ],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'البريد الإلكتروني أو رقم الهاتف مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ];
    }
}
