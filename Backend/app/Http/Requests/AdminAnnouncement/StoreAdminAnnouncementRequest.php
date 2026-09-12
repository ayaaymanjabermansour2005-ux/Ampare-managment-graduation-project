<?php

namespace App\Http\Requests\AdminAnnouncement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
            'audience' => ['required', Rule::in(['all', 'subscriber', 'generator_owner', 'technician'])],
        ];
    }
}
