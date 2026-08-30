<?php

namespace App\Http\Requests\TechnicianPayment;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianPaymentAttachmentRequest extends FormRequest
{
    private const ALLOWED_DOCUMENT_TYPES = [
        DocumentType::PaymentReceipt->value,
        DocumentType::Other->value,
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
                'max:'.config('attachments.max_size_kb'),
                'mimetypes:'.implode(',', config('attachments.allowed_mimes')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => 'يجب تحديد نوع المستند.',
            'file.required' => 'يجب اختيار ملف.',
            'file.max' => 'حجم الملف يتجاوز الحد المسموح.',
            'file.mimetypes' => 'نوع الملف غير مدعوم.',
        ];
    }
}
