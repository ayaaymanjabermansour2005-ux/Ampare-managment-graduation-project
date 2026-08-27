<?php

namespace App\Http\Requests\Offer;

use App\Enums\BeneficiaryType;
use App\Enums\OfferDiscountType;
use App\Enums\OfferTargetMode;
use App\Models\Subscriber;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::enum(OfferDiscountType::class)],
            'discount_value' => ['required', 'numeric', 'min:0.01'],

            'target_mode' => ['required', Rule::enum(OfferTargetMode::class)],

            'beneficiary_type' => [
                Rule::requiredIf(fn () => $this->input('target_mode') === OfferTargetMode::Beneficiary->value),
                Rule::excludeIf(fn () => $this->input('target_mode') !== OfferTargetMode::Beneficiary->value),
                Rule::enum(BeneficiaryType::class),
            ],

            'subscriber_ids' => [
                Rule::requiredIf(fn () => $this->input('target_mode') === OfferTargetMode::Selected->value),
                Rule::excludeIf(fn () => $this->input('target_mode') !== OfferTargetMode::Selected->value),
                'array',
                'min:1',
            ],
            'subscriber_ids.*' => ['integer', 'exists:subscribers,id'],

            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'discount_type.enum' => 'نوع الخصم غير صالح.',
            'discount_value.min' => 'قيمة الخصم يجب أن تكون أكبر من صفر.',
            'target_mode.enum' => 'طريقة استهداف العرض غير صالحة.',
            'beneficiary_type.required_if' => 'يجب تحديد فئة المستفيدين عند اختيار الاستهداف حسب الفئة.',
            'subscriber_ids.required_if' => 'يجب تحديد المشتركين المستهدفين عند اختيار عرض خاص.',
            'end_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء.',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (
                $this->input('discount_type') === OfferDiscountType::Percentage->value
                && $this->filled('discount_value')
                && (float) $this->input('discount_value') > 100
            ) {
                $validator->errors()->add('discount_value', 'نسبة الخصم لا يمكن أن تتجاوز 100%.');
            }

            if ($this->input('target_mode') === OfferTargetMode::Selected->value && $this->filled('subscriber_ids')) {
                $this->validateSubscribersBelongToUser($validator);
            }
        });
    }

    private function validateSubscribersBelongToUser(ValidatorContract $validator): void
    {
        $user = $this->user();

        $validSubscriberIds = Subscriber::whereIn('id', $this->input('subscriber_ids'))
            ->whereHas('subscriptions', fn ($q) => $q->whereHas('generator', fn ($g) => $g->where('owner_id', $user->id)))
            ->pluck('id')
            ->all();

        $invalidIds = array_diff($this->input('subscriber_ids'), $validSubscriberIds);

        if (! empty($invalidIds)) {
            $validator->errors()->add('subscriber_ids', 'بعض المشتركين المحددين غير تابعين لمولداتك.');
        }
    }
}
