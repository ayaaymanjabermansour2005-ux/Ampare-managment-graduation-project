<?php

namespace App\Http\Requests\Fault;

use App\Enums\FaultPriority;
use App\Enums\SubscriptionStatus;
use App\Models\Generator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'generator_id' => [
                'required',
                'integer',
                'exists:generators,id',
                function ($attribute, $value, $fail) use ($user) {
                    if ($user->isAdmin()) {
                        return;
                    }

                    $generator = Generator::find($value);

                    if ($user->isOwner()) {
                        if (! $generator || $generator->owner_id !== $user->id) {
                            $fail('هذا المولد ليس ضمن مولداتك.');
                        }

                        return;
                    }

                    if ($user->isSubscriber()) {
                        $hasSubscription = $generator?->subscriptions()
                            ->whereHas('subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $user->id))
                            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Pending->value])
                            ->exists();

                        if (! $hasSubscription) {
                            $fail('لا يوجد لديك اشتراك على هذا المولد.');
                        }
                    }
                },
            ],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['sometimes', Rule::enum(FaultPriority::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'generator_id.required' => 'يجب اختيار المولد.',
            'title.required' => 'عنوان العطل مطلوب.',
            'description.required' => 'وصف العطل مطلوب.',
            'priority.enum' => 'أولوية العطل غير صالحة.',
        ];
    }
}
