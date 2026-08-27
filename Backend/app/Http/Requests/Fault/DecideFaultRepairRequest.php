<?php

namespace App\Http\Requests\Fault;

use App\Enums\FaultRepairMethod;
use App\Models\Technician;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideFaultRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'repair_method' => ['required', Rule::enum(FaultRepairMethod::class)],

            'technician_id' => [
                'nullable',
                'integer',
                'exists:technicians,id',
                function ($attribute, $value, $fail) use ($user) {
                    if (! $value || $this->input('repair_method') !== FaultRepairMethod::InternalTechnician->value) {
                        return;
                    }

                    if ($user->isAdmin()) {
                        return;
                    }

                    $technician = Technician::find($value);

                    if (! $technician || $technician->owner_id !== $user->id) {
                        $fail('هذا الفني ليس ضمن فنييك الخاصين.');
                    }
                },
            ],

            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'repair_method.required' => 'يجب تحديد طريقة الإصلاح.',
            'repair_method.enum' => 'طريقة الإصلاح غير صالحة.',
            'technician_id.exists' => 'الفني المحدد غير موجود.',
        ];
    }
}
