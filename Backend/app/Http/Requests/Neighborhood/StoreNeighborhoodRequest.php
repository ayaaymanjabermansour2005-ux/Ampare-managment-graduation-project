<?php

namespace App\Http\Requests\Neighborhood;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNeighborhoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ignoreId = $this->route('neighborhood')?->id;

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('neighborhoods', 'name')->ignore($ignoreId),
            ],
            'name_en' => [
                'nullable',
                'string',
                'min:2',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'يوجد حي بنفس هذا الاسم مسبقًا.',
        ];
    }
}
