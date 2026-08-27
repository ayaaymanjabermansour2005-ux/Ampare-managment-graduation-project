<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use App\Http\Requests\Concerns\NormalizesPhoneNumber;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use NormalizesPhoneNumber;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User|null $target */
        $target = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:100'],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+?[0-9]{7,15}$/',
                Rule::unique('users', 'phone')->ignore($target?->id)->whereNull('deleted_at'),
            ],

            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target?->id)->whereNull('deleted_at')],

            'status' => [
                ($this->user()?->isAdmin() ?? false) ? 'sometimes' : 'prohibited',
                Rule::enum(UserStatus::class),
            ],

            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'whatsapp' => ['nullable', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.prohibited' => 'لا تملك صلاحية تعديل حالة الحساب.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم مسبقاً.',
            'phone.regex' => 'صيغة رقم الهاتف غير صحيحة.',
            'phone.unique' => 'رقم الهاتف هذا مستخدم مسبقًا من قِبل حساب آخر.',
            'birth_date.before' => 'تاريخ الميلاد يجب أن يكون بالماضي.',
            'whatsapp.regex' => 'صيغة رقم واتساب غير صحيحة.',
        ];
    }
}
