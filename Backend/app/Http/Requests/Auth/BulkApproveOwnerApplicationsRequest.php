<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkApproveOwnerApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_ids' => ['required', 'array', 'min:1', 'max:100'],
            'application_ids.*' => [
                'integer',
                Rule::exists('owner_applications', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'application_ids.required' => 'يجب تحديد طلب واحد على الأقل.',
            'application_ids.max' => 'لا يمكن معالجة أكثر من 100 طلب دفعة واحدة.',
            'application_ids.*.exists' => 'أحد الطلبات المحددة غير موجود.',
        ];
    }
}
