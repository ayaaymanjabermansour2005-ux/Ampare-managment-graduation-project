<?php

namespace App\Http\Requests\Technician;

use App\Enums\Role as RoleEnum;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'يجب تحديد المستخدم المراد ربطه كفني.',
            'user_id.exists' => 'المستخدم المحدَّد غير موجود.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('user_id')) {
                return;
            }

            /** @var User|null $targetUser */
            $targetUser = User::find($this->input('user_id'));

            if ($targetUser && ! $targetUser->hasRole(RoleEnum::TECHNICIAN->value)) {
                $validator->errors()->add('user_id', 'هذا المستخدم لا يملك دور الفني.');
            }

            $existing = Technician::where('user_id', $this->input('user_id'))->exists();
            if ($existing) {
                $validator->errors()->add('user_id', 'يوجد ملف فني مرتبط بهذا المستخدم بالفعل.');
            }
        });
    }
}
