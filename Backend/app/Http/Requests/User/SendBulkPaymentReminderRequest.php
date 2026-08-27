<?php

namespace App\Http\Requests\User;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendBulkPaymentReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscriber_ids' => ['required', 'array', 'min:1', 'max:200'],
            'subscriber_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subscriber_ids.required' => 'يجب تحديد مشترك واحد على الأقل.',
            'subscriber_ids.max' => 'لا يمكن إرسال تذكير لأكثر من 200 مشترك دفعة واحدة.',
            'subscriber_ids.*.exists' => 'أحد المشتركين المحددين غير موجود.',
        ];
    }

    protected function passedValidation(): void
    {
        $validSubscriberIds = User::whereIn('id', $this->input('subscriber_ids'))
            ->whereHas('roles', fn($q) => $q->where('name', Role::SUBSCRIBER->value))
            ->pluck('id')
            ->all();

        $this->merge(['subscriber_ids' => $validSubscriberIds]);
    }
}
