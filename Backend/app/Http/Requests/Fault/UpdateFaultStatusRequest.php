<?php

namespace App\Http\Requests\Fault;

use App\Enums\FaultStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFaultStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(FaultStatus::class)],
            'admin_override_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'يجب تحديد الحالة الجديدة.',
            'status.enum' => 'حالة العطل غير صالحة.',
            'admin_override_reason.required' => 'يجب توضيح سبب التجاوز الاستثنائي (مثلًا: إغلاق عطل بالخطأ، تصحيح بعد مراجعة).',
        ];
    }
}
