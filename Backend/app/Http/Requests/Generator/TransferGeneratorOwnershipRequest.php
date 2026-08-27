<?php

namespace App\Http\Requests\Generator;

use App\Enums\Role;
use App\Models\Generator;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferGeneratorOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Generator $generator */
        $generator = $this->route('generator');

        return $this->user()->can('transferOwnership', $generator);
    }

    public function rules(): array
    {
        return [
            'owner_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    /** @var Generator $generator */
                    $generator = $this->route('generator');

                    if ((int) $value === $generator->owner_id) {
                        $fail('هذا المولد مملوك أصلًا لهذا المستخدم.');

                        return;
                    }

                    $newOwner = User::find($value);

                    if ($newOwner && ! $newOwner->hasRole(Role::GENERATOR_OWNER->value)) {
                        $fail('المستخدم المحدد ليس له دور "صاحب مولد" — يجب اختيار مالك مولد مسجَّل.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_id.required' => 'يجب تحديد المالك الجديد.',
            'owner_id.exists' => 'المستخدم المحدد غير موجود.',
        ];
    }
}
