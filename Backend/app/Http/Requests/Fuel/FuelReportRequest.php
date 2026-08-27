<?php

namespace App\Http\Requests\Fuel;

use Illuminate\Foundation\Http\FormRequest;

class FuelReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from', 'before_or_equal:today'],
        ];
    }
}
