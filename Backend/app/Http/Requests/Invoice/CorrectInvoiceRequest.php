<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class CorrectInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'final_amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
