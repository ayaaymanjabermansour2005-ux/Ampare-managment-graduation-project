<?php

namespace App\Http\Requests\Technician;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianAttachmentRequest extends FormRequest
{
    private const ALLOWED_DOCUMENT_TYPES = [
        DocumentType::TechnicianCertificate->value,
        DocumentType::TechnicianIdentity->value,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(self::ALLOWED_DOCUMENT_TYPES)],
            'file' => [
                'required',
                'file',
                'max:' . config('attachments.max_size_kb'),
                'mimetypes:' . implode(',', config('attachments.allowed_mimes')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => 'يجب تحديد نوع المستند.',
            'document_type.in' => 'نوع المستند غير مسموح للفنيين (شهادة/هوية فقط).',
            'file.required' => 'يجب اختيار ملف.',
            'file.max' => 'حجم الملف يتجاوز الحد المسموح.',
            'file.mimetypes' => 'نوع الملف غير مدعوم.',
        ];
    }
}
