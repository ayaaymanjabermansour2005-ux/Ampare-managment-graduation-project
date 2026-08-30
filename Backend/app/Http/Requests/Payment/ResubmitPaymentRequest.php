<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use App\Services\ExchangeRateService;
use App\Support\Payment\InvoiceBalanceValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResubmitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Payment $payment */
        $payment = $this->route('payment');

        return $this->user()->can('resubmit', $payment);
    }

    public function rules(): array
    {
        /** @var Payment $payment */
        $payment = $this->route('payment');

        return [
            'amount' => [
                'sometimes',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($payment) {
                    $invoice = $payment->invoice()->first();

                    if (! $invoice) {
                        return;
                    }

                    try {
                        [, $amountIls] = app(ExchangeRateService::class)->toIls((float) $value, $payment->currency);
                    } catch (ValidationException $e) {
                        $fail($e->validator->errors()->first('currency') ?? 'تعذّر التحقق من سعر الصرف حاليًا.');

                        return;
                    }
                    $validator = app(InvoiceBalanceValidator::class);

                    if (! $validator->isWithinRemainingBalance($invoice, $amountIls, excludingPaymentId: $payment->id)) {
                        $remainingIls = $validator->remainingIls($invoice, excludingPaymentId: $payment->id);

                        $fail("المبلغ (يعادل {$amountIls} شيكل) أكبر من الرصيد المتبقي على الفاتورة ({$remainingIls} شيكل).");
                    }
                },
            ],

            'transaction_reference' => ['nullable', 'string', 'max:100', Rule::unique('payments', 'transaction_reference')->ignore($payment->id)],
            'note' => ['nullable', 'string', 'max:500'],

            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:'.config('attachments.max_size_kb'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.*.mimes' => 'المرفقات يجب أن تكون JPG أو JPEG أو PNG أو PDF.',
            'attachments.*.max' => 'حجم كل مرفق يجب ألا يتجاوز '.round(config('attachments.max_size_kb') / 1024, 1).' ميجابايت.',
        ];
    }
}
