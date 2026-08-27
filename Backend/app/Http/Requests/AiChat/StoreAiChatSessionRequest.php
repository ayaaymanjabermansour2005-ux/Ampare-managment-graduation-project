<?php

namespace App\Http\Requests\AiChat;

use App\Models\Generator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreAiChatSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'generator_id' => [
                $this->user()?->isAdmin() ? 'nullable' : 'required',
                'integer',
                'exists:generators,id',
            ],
            'message' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
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
                $validator->errors()->add('generator_id', 'لا يمكنك بدء محادثة عن هذا المولد — لا توجد علاقة تربطك به.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'generator_id.required' => 'يجب تحديد المولد المراد الاستفسار عنه.',
        ];
    }
}
