<?php

namespace App\Http\Requests\Offer;

use App\Enums\OfferDiscountType;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_value' => ['sometimes', 'numeric', 'min:0.01'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $offer = $this->route('offer');

            if (
                $offer
                && $offer->discount_type === OfferDiscountType::Percentage
                && $this->filled('discount_value')
                && (float) $this->input('discount_value') > 100
            ) {
                $validator->errors()->add('discount_value', 'نسبة الخصم لا يمكن أن تتجاوز 100%.');
            }
        });
    }
}
