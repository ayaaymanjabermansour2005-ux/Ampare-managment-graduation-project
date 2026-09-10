<?php

namespace App\Http\Requests\Complaint;

use App\Enums\ComplaintStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                ComplaintStatus::InProgress->value,
                ComplaintStatus::WaitingSubscriber->value,
                ComplaintStatus::Resolved->value,
            ])],
            'resolution_note' => ['nullable', 'string', 'max:2000', 'required_if:status,'.ComplaintStatus::Resolved->value],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'حالة غير صالحة.',
            'resolution_note.required_if' => 'يجب كتابة ملاحظة توضح كيف تم حل الشكوى.',
        ];
    }
}
