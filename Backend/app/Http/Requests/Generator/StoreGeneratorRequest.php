<?php

namespace App\Http\Requests\Generator;

use App\Enums\Currency;
use App\Enums\FuelType;
use App\Enums\GeneratorStatus;
use App\Enums\OperatingSchedule;
use App\Enums\Role;
use App\Models\Generator;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeneratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Generator::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'price_per_kw' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'currency' => ['sometimes', Rule::enum(Currency::class)],
            'capacity_kw' => ['nullable', 'integer', 'min:1'],
            'fuel_type' => ['nullable', Rule::enum(FuelType::class)],
            'tank_capacity_liters' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines_count' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'rated_voltage' => ['nullable', 'integer', 'min:1', 'max:65000'],
            'rated_frequency_hz' => ['nullable', 'integer', 'in:50,60'],
            'phase_count' => ['nullable', 'integer', 'in:1,3'],
            'rated_load_kw' => ['nullable', 'integer', 'min:1'],
            'service_interval_hours' => ['nullable', 'integer', 'min:1'],
            'next_service_due_at' => ['nullable', 'date'],
            'installed_at' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['sometimes', Rule::enum(GeneratorStatus::class)],
            'operating_schedule' => ['required', Rule::enum(OperatingSchedule::class)],
            'operating_start_time' => ['required_if:operating_schedule,custom', 'date_format:H:i'],
            'operating_end_time' => ['required_if:operating_schedule,custom', 'date_format:H:i', 'after:operating_start_time'],
        ];

        if ($this->user()?->isAdmin() ?? false) {
            $rules['owner_id'] = [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $owner = User::find($value);

                    if (! $owner || ! $owner->hasRole(Role::GENERATOR_OWNER->value)) {
                        $fail('مالك المولد المحدد غير صالح — يجب أن يكون بدور generator_owner.');
                    }
                },
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المولد مطلوب.',
            'price_per_kw.required' => 'سعر الكيلووات مطلوب.',
            'price_per_kw.numeric' => 'سعر الكيلووات يجب أن يكون رقمًا.',
            'currency.enum' => 'العملة يجب أن تكون ILS أو USD.',
            'status.enum' => 'حالة المولد غير صالحة.',
            'operating_schedule.enum' => 'فترة تشغيل المولد غير صالحة.',
            'location_id.exists' => 'الموقع المحدد غير موجود.',
            'owner_id.required' => 'يجب تحديد مالك المولد.',
            'owner_id.exists' => 'مالك المولد المحدد غير موجود.',
            'operating_start_time.required_if' => 'يجب تحديد وقت البدء عند اختيار فترة تشغيل مخصصة.',
            'operating_end_time.after' => 'وقت الانتهاء يجب أن يكون بعد وقت البدء.',
        ];
    }
}
