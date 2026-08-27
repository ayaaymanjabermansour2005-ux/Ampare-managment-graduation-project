<?php

namespace App\Http\Requests\ContactMessage;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'email' => ['required', 'email', 'max:150'],
            'subject' => ['required', 'in:general,owner,technical,partnership'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'phone.required' => 'رقم الجوال مطلوب.',
            'phone.regex' => 'صيغة رقم الجوال غير صحيحة.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'message.required' => 'الرسالة مطلوبة.',
            'message.min' => 'الرسالة قصيرة جدًا.',
        ];
    }
}
