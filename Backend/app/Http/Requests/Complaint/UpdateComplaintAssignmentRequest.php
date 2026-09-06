<?php

namespace App\Http\Requests\Complaint;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'المستخدم المحدَّد غير موجود.',
        ];
    }

    /**
     * "المسؤول" عن شكوى لازم يكون حساب أدمن (لا يوجد عمود role على جدول
     * users — الأدوار عبر Spatie HasRoles — فالتحقق هون بعد فشل/نجاح قواعد
     * exists الأساسية، مش عبر Rule::exists declarative).
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (! $this->filled('assigned_to')) {
                return;
            }

            $user = User::find($this->input('assigned_to'));

            if (! $user || ! $user->isAdmin()) {
                $validator->errors()->add('assigned_to', 'يجب أن يكون المسؤول حساب أدمن.');
            }
        });
    }
}
