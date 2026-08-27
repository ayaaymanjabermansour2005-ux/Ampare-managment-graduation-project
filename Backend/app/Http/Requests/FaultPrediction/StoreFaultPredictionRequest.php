<?php

namespace App\Http\Requests\FaultPrediction;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaultPredictionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'generator_id' => ['required', 'integer', 'exists:generators,id'],
            'prediction_type' => ['required', 'string', 'max:100'],
            'confidence' => ['required', 'numeric', 'between:0,1'],
            'recommendation' => ['nullable', 'string', 'max:1000'],
            'input_snapshot' => ['nullable', 'array'],
        ];
    }
}
