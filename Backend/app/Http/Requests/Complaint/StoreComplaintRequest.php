<?php

namespace App\Http\Requests\Complaint;

use App\Enums\Role;
use App\Models\Complaint;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'complainable_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(Complaint::COMPLAINABLE_TYPES))],
            'complainable_id' => ['required_with:complainable_type', 'integer'],
            'subject' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'complainable_type.in' => 'نوع الكيان غير مدعوم.',
            'complainable_id.required_with' => 'يجب تحديد الكيان عند اختيار نوعه.',
            'subject.required' => 'عنوان الشكوى مطلوب.',
            'description.required' => 'تفاصيل الشكوى مطلوبة.',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (! $this->filled('complainable_type')) {
                return;
            }

            $modelClass = Complaint::COMPLAINABLE_TYPES[$this->input('complainable_type')] ?? null;
            $model = $modelClass ? $modelClass::find($this->input('complainable_id')) : null;

            if (! $model) {
                $validator->errors()->add('complainable_id', 'الكيان المحدد غير موجود.');

                return;
            }

            if (! $this->userCanReferenceComplainable($model)) {
                $validator->errors()->add('complainable_id', 'لا يمكنك تقديم شكوى بخصوص هذا الكيان.');
            }
        });
    }

    private function userCanReferenceComplainable(Model $model): bool
    {
        $user = $this->user();

        if ($user->hasRole(Role::ADMIN->value)) {
            return true;
        }

        return match (true) {
            $model instanceof Generator => $this->generatorRelatedToUser($model, $user),
            $model instanceof Fault => $this->generatorRelatedToUser($model->generator, $user),
            $model instanceof Subscription => $this->subscriptionRelatedToUser($model, $user),
            $model instanceof Invoice => $this->subscriptionRelatedToUser($model->subscription, $user),
            $model instanceof Payment => $this->subscriptionRelatedToUser($model->invoice?->subscription, $user),
            $model instanceof User => true,
            default => false,
        };
    }

    private function generatorRelatedToUser(?Generator $generator, User $user): bool
    {
        if (! $generator) {
            return false;
        }

        if ($generator->owner_id === $user->id) {
            return true;
        }

        return $generator->subscriptions()
            ->whereHas('subscriberMeter.subscriber', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    private function subscriptionRelatedToUser(?Subscription $subscription, User $user): bool
    {
        if (! $subscription) {
            return false;
        }

        if ($subscription->generator?->owner_id === $user->id) {
            return true;
        }

        return $subscription->subscriberMeter?->subscriber?->user_id === $user->id;
    }
}
