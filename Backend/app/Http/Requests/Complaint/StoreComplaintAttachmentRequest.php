<?php

namespace App\Http\Requests\Complaint;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintAttachmentRequest extends FormRequest
{
    private const ALLOWED_TYPES = [
        DocumentType::ComplaintImage->value,
        DocumentType::ComplaintVideo->value,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,mp4,mov'],
            'document_type' => ['required', Rule::in(self::ALLOWED_TYPES)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'يجب اختيار صورة أو فيديو.',
            'file.mimes' => 'الصيغ المسموحة: jpg, jpeg, png, mp4, mov.',
            'document_type.required' => 'يجب تحديد نوع المرفق.',
        ];
    }
}
