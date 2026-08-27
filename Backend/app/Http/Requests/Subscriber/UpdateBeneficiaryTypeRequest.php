<?php

namespace App\Http\Requests\Subscriber;

use App\Models\Subscriber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBeneficiaryTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Subscriber $subscriber */
        $subscriber = $this->route('subscriber');

        return $this->user()->can('updateBeneficiaryType', $subscriber);
    }

    public function rules(): array
    {
        return [
            'beneficiary_type' => ['required', Rule::in(['normal', 'special'])],
        ];
    }
}
