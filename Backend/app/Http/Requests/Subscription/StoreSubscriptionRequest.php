<?php

namespace App\Http\Requests\Subscription;

use App\Enums\BillingCycle;
use App\Enums\OperatingSchedule;
use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\DB;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscriber_meter_id' => ['required', 'integer', 'exists:subscriber_meters,id'],
            'generator_id' => ['required', 'integer', 'exists:generators,id'],
            'requested_capacity_kw' => ['nullable', 'numeric', 'min:0.1'],
            'schedule' => ['required', Rule::enum(OperatingSchedule::class)],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'service_start_time' => ['required_if:schedule,custom', 'date_format:H:i'],
            'service_end_time' => ['required_if:schedule,custom', 'date_format:H:i', 'after:service_start_time'],
            'contract_type' => ['nullable', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'subscriber_meter_id.required' => 'يجب تحديد العداد الخاص بهذا العقد.',
            'schedule.enum' => 'فترة الاشتراك المحددة غير صالحة.',
            'service_start_time.required_if' => 'يجب تحديد وقت البدء عند اختيار فترة مخصصة.',
            'service_end_time.after' => 'وقت الانتهاء يجب أن يكون بعد وقت البدء.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled(['subscriber_meter_id', 'generator_id', 'schedule'])) {
                return;
            }

            $exists = DB::table('subscriptions')
                ->where('subscriber_meter_id', $this->input('subscriber_meter_id'))
                ->where('generator_id', $this->input('generator_id'))
                ->where('schedule', $this->input('schedule'))
                ->where('service_start_time', $this->input('service_start_time') ?: '00:00:00')
                ->where('service_end_time', $this->input('service_end_time') ?: '00:00:00')
                ->whereIn('status', Subscription::DUPLICATE_BLOCKING_STATUSES)
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'subscriber_meter_id',
                    'يوجد اشتراك آخر (pending أو active) بنفس العداد والمولد ونفس فترة التشغيل مسبقًا.'
                );
            }
        });
    }
}
