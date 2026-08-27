<?php

namespace App\Http\Requests\Conversation;

use App\Models\Generator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertConversationToIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'in:fault,complaint'],
            'generator_id' => [
                Rule::requiredIf(fn () => $this->input('category') === 'fault'),
                'nullable',
                'integer',
                'exists:generators,id',
            ],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (! $this->filled('generator_id')) {
                return;
            }

            $generator = Generator::find($this->input('generator_id'));

            if ($generator && ! $this->user()->can('view', $generator)) {
                $validator->errors()->add('generator_id', 'لا يمكنك ربط هذا العطل بهذا المولد — لا توجد علاقة تربطك به.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'category.required' => 'يجب تحديد نوع التحويل (بلاغ عطل أو شكوى).',
            'generator_id.required' => 'يجب تحديد المولد المراد الإبلاغ عن عطله.',
        ];
    }
}
