<?php

namespace App\Http\Requests\MeterReading;

use App\Models\MeterReading;
use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MeterReading::class);
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'subscription_id' => [
                'required',
                'integer',
                'exists:subscriptions,id',
                function ($attribute, $value, $fail) use ($user) {
                    if ($user->isAdmin()) {
                        return;
                    }

                    $subscription = Subscription::with('generator')->find($value);

                    if (! $subscription) {
                        $fail('هذا الاشتراك لا يتبع لأحد مولداتك.');

                        return;
                    }

                    if ($user->isOwner()) {
                        if ($subscription->generator?->owner_id !== $user->id) {
                            $fail('هذا الاشتراك لا يتبع لأحد مولداتك.');
                        }

                        return;
                    }

                    if ($user->isTechnician()) {
                        $isLinked = $subscription->generator
                            ?->technicians()
                            ->where('technicians.user_id', $user->id)
                            ->exists() ?? false;

                        if (! $isLinked) {
                            $fail('هذا الاشتراك لا يتبع لأحد المولدات المسندة إليك.');
                        }

                        return;
                    }

                    $fail('هذا الاشتراك لا يتبع لأحد مولداتك.');
                },
            ],
            'reading_date' => ['required', 'date', 'before_or_equal:today'],
            'current_reading' => [
                'required',
                'numeric',
                'min:0',

                function ($attribute, $value, $fail) {
                    $subscriptionId = $this->input('subscription_id');

                    if (! $subscriptionId) {
                        return;
                    }

                    $previousReading = MeterReading::where('subscription_id', $subscriptionId)
                        ->orderByDesc('reading_date')
                        ->value('current_reading');

                    if ($previousReading !== null && (float) $value < (float) $previousReading) {
                        $fail('لا يمكن أن تكون القراءة الحالية أقل من آخر قراءة مسجّلة ('.$previousReading.').');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_id.required' => 'يجب اختيار الاشتراك.',
            'subscription_id.exists' => 'الاشتراك المحدد غير موجود.',
            'reading_date.before_or_equal' => 'تاريخ القراءة لا يمكن أن يكون بالمستقبل.',
            'current_reading.required' => 'قيمة القراءة الحالية مطلوبة.',
        ];
    }
}
