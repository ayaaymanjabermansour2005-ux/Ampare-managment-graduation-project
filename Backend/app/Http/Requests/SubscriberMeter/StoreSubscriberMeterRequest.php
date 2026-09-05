<?php

namespace App\Http\Requests\SubscriberMeter;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriberMeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isAdmin = $this->user()?->isAdmin() ?? false;

        return [
            'meter_number' => ['required', 'string', 'max:50', Rule::unique('subscriber_meters', 'meter_number')->whereNull('deleted_at')],
            'property_label' => ['nullable', 'string', 'max:150'],

            // مطلوب فقط لما الأدمن ينشئ عداد نيابةً عن مشترك (مثلًا ضمن نموذج
            // "إضافة اشتراك" — SubscribersView.vue)؛ محظور بمسار المشترك
            // الذاتي (العداد بياخد subscriber_id من $user->subscriber مباشرة).
            'user_id' => [
                $isAdmin ? 'required' : 'prohibited',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'meter_number.required' => 'رقم العداد مطلوب.',
            'meter_number.unique' => 'رقم العداد هذا مسجّل مسبقاً.',
            'user_id.required' => 'يجب تحديد المشترك صاحب العداد.',
            'user_id.exists' => 'المستخدم المحدَّد غير موجود.',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (! $this->user()?->isAdmin() || ! $this->filled('user_id')) {
                return;
            }

            $target = User::find((int) $this->input('user_id'));

            if ($target && ! $target->hasRole(RoleEnum::SUBSCRIBER->value)) {
                $validator->errors()->add('user_id', 'هذا المستخدم لا يملك دور المشترك.');
            }
        });
    }
}
