<?php

namespace App\Http\Requests\Generator;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeneratorAttachmentRequest extends FormRequest
{
    private const ALLOWED_DOCUMENT_TYPES = [
        DocumentType::GeneratorPhoto->value,
        DocumentType::GeneratorLicense->value,
        DocumentType::GeneratorPurchaseInvoice->value,
    ];

    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
                'mimetypes:'.implode(',', self::ALLOWED_MIMES),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => 'يجب تحديد نوع المستند.',
            'document_type.in' => 'نوع المستند غير مسموح للمولدات (صورة/رخصة/فاتورة شراء فقط).',
            'file.required' => 'يجب اختيار ملف.',
            'file.max' => 'حجم الملف يتجاوز الحد المسموح.',
            'file.mimetypes' => 'نوع الملف غير مدعوم. المسموح: صور، PDF، Word، Excel.',
        ];
    }
}
