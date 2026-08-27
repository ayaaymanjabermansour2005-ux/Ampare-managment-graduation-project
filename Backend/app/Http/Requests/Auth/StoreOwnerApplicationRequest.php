<?php

namespace App\Http\Requests\Auth;

use App\Enums\Currency;
use App\Enums\OwnerApplicationStatus;
use App\Http\Requests\Concerns\NormalizesPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreOwnerApplicationRequest extends FormRequest
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

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
                Rule::unique('owner_applications', 'email')->where('status', OwnerApplicationStatus::Pending->value),
            ],

            'phone' => [
                'nullable',
                'string',
                'regex:/^\+?[0-9]{7,15}$/',
                Rule::unique('users', 'phone')->whereNull('deleted_at'),
                Rule::unique('owner_applications', 'phone')->where('status', OwnerApplicationStatus::Pending->value),
            ],

            'notes' => ['nullable', 'string', 'max:2000'],

            'password' => [
                'required',
                'confirmed',
                $this->passwordRule(),
            ],

            'generator_name' => ['required', 'string', 'min:3', 'max:150'],
            'generator_price_per_kw' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'generator_currency' => ['sometimes', Rule::enum(Currency::class)],
            'generator_capacity_kw' => ['nullable', 'integer', 'min:1'],
            'generator_city' => ['required', 'string', 'max:100'],
            'generator_neighborhood_id' => ['nullable', 'integer', 'exists:neighborhoods,id'],
            'generator_address' => ['nullable', 'string', 'max:255'],
            'generator_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'generator_longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'id_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.config('attachments.max_size_kb')],
            'business_license' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.config('attachments.max_size_kb')],
            'generator_photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('attachments.max_size_kb')],
            'ownership_contract' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.config('attachments.max_size_kb')],
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
            'email.unique' => 'هذا البريد الإلكتروني مستخدم مسبقاً أو له طلب انضمام قيد المراجعة حاليًا.',
            'phone.regex' => 'صيغة رقم الهاتف غير صحيحة.',
            'phone.unique' => 'رقم الهاتف هذا مستخدم مسبقًا أو له طلب انضمام قيد المراجعة حاليًا.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 2000 حرف.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.uncompromised' => 'كلمة المرور هذه معروفة ضمن تسريبات بيانات سابقة، اختر كلمة مرور أخرى.',

            'generator_name.required' => 'اسم المولد مطلوب.',
            'generator_price_per_kw.required' => 'سعر الكيلووات مطلوب.',
            'generator_price_per_kw.numeric' => 'سعر الكيلووات يجب أن يكون رقمًا.',
            'generator_city.required' => 'مدينة المولد مطلوبة.',
            'generator_neighborhood_id.exists' => 'الحي المحدَّد غير موجود.',

            'id_document.required' => 'صورة الهوية مطلوبة.',
            'business_license.required' => 'الرخصة التجارية مطلوبة.',
            'generator_photo.required' => 'صورة المولد مطلوبة.',
            'ownership_contract.required' => 'عقد الملكية مطلوب.',
            'id_document.mimes' => 'صيغة صورة الهوية يجب أن تكون PDF أو JPG أو PNG أو WEBP.',
            'business_license.mimes' => 'صيغة الرخصة التجارية يجب أن تكون PDF أو JPG أو PNG أو WEBP.',
            'generator_photo.mimes' => 'صيغة صورة المولد يجب أن تكون JPG أو PNG أو WEBP.',
            'ownership_contract.mimes' => 'صيغة عقد الملكية يجب أن تكون PDF أو JPG أو PNG أو WEBP.',
            'id_document.max' => 'حجم الملف يجب ألا يتجاوز '.round(config('attachments.max_size_kb') / 1024, 1).' ميجابايت.',
            'business_license.max' => 'حجم الملف يجب ألا يتجاوز '.round(config('attachments.max_size_kb') / 1024, 1).' ميجابايت.',
            'generator_photo.max' => 'حجم الملف يجب ألا يتجاوز '.round(config('attachments.max_size_kb') / 1024, 1).' ميجابايت.',
            'ownership_contract.max' => 'حجم الملف يجب ألا يتجاوز '.round(config('attachments.max_size_kb') / 1024, 1).' ميجابايت.',
        ];
    }
}
