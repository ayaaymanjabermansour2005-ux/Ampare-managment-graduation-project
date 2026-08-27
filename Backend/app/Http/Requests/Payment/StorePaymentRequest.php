<?php

namespace App\Http\Requests\Payment;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethodType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\ExchangeRateService;
use App\Support\Payment\InvoiceBalanceValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    protected function paymentMethod(): ?PaymentMethod
    {
        return once(function () {

            return PaymentMethod::find(
                $this->input('payment_method_id')
            );
        });
    }

    protected function effectiveCurrency(): ?Currency
    {
        if ($this->user()?->isAdmin()) {
            $invoice = Invoice::find($this->input('invoice_id'));

            return $invoice?->currency;
        }

        $method = $this->paymentMethod();

        if ($method && $method->type !== PaymentMethodType::Cash) {
            return $method->currency;
        }

        return Currency::tryFrom((string) $this->input('currency'));
    }

    /**
     * @return array{amount_ils: float, remaining_ils: float, is_within_balance: bool}|null 
     */
    protected function balanceCheck(): ?array
    {
        return once(function () {

            $invoice = Invoice::find($this->input('invoice_id'));

            if (! $invoice) {
                return null;
            }

            $currency = $this->effectiveCurrency();

            if (! $currency) {
                return null;
            }

            try {
                [, $amountIls] = app(ExchangeRateService::class)
                    ->toIls((float) $this->input('amount'), $currency);
            } catch (ValidationException) {
                return null;
            }

            $validator = app(InvoiceBalanceValidator::class);

            return [
                'amount_ils' => $amountIls,
                'remaining_ils' => $validator->remainingIls($invoice),
                'is_within_balance' => $validator->isWithinRemainingBalance($invoice, $amountIls),
            ];
        });
    }

    public function rules(): array
    {
        $user = $this->user();
        $isAdmin = $user?->isAdmin() ?? false;

        $method = $isAdmin ? null : $this->paymentMethod();
        $methodHasFixedCurrency = $method && $method->type !== PaymentMethodType::Cash;

        return [

            'invoice_id' => [
                'required',
                'integer',
                'exists:invoices,id',

                function ($attribute, $value, $fail) use ($user, $isAdmin) {

                    if ($isAdmin) {
                        return;
                    }

                    $invoice = Invoice::with(
                        'subscription.subscriberMeter.subscriber'
                    )->find($value);

                    if (
                        ! $invoice ||
                        $invoice->subscription?->subscriberMeter?->subscriber?->user_id !== $user->id
                    ) {
                        $fail('هذه الفاتورة لا تخصك.');

                        return;
                    }

                    if ($invoice->status === InvoiceStatus::Paid) {
                        $fail('هذه الفاتورة مدفوعة بالكامل.');
                    }

                    if ($invoice->status === InvoiceStatus::Cancelled) {
                        $fail('لا يمكن الدفع على فاتورة ملغاة.');
                    }
                },
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',

                function ($attribute, $value, $fail) use ($isAdmin) {

                    if ($isAdmin) {
                        return;
                    }

                    $invoice = Invoice::find($this->input('invoice_id'));

                    if (! $invoice) {
                        return;
                    }

                    $currency = $this->effectiveCurrency();

                    if (! $currency) {
                        return;
                    }

                    try {
                        [, $amountIls] = app(ExchangeRateService::class)->toIls((float) $value, $currency);
                    } catch (ValidationException $e) {
                        $fail($e->validator->errors()->first('currency') ?? 'تعذّر التحقق من سعر الصرف حاليًا.');

                        return;
                    }

                    $validator = app(InvoiceBalanceValidator::class);

                    if (! $validator->isWithinRemainingBalance($invoice, $amountIls)) {
                        $remainingIls = $validator->remainingIls($invoice);

                        $fail(
                            "المبلغ (يعادل {$amountIls} شيكل) أكبر من الرصيد المتبقي على الفاتورة ({$remainingIls} شيكل)."
                        );
                    }
                },
            ],

            'payment_method_id' => [
                $isAdmin ? 'prohibited' : 'required',
                'integer',
                'exists:payment_methods,id',

                function ($attribute, $value, $fail) use ($isAdmin) {

                    if ($isAdmin || ! $value) {
                        return;
                    }

                    $invoice = Invoice::with(
                        'subscription.generator'
                    )->find(
                        $this->input('invoice_id')
                    );

                    $expectedOwnerId = $invoice?->subscription?->generator?->owner_id;

                    $method = $this->paymentMethod();

                    if (! $method) {
                        $fail('وسيلة الدفع غير موجودة.');

                        return;
                    }

                    if (
                        ! $expectedOwnerId ||
                        $method->user_id !== $expectedOwnerId
                    ) {
                        $fail(
                            'وسيلة الدفع المحددة لا تخص مالك هذا المولد.'
                        );
                    }

                    if ($method->trashed()) {
                        $fail(
                            'وسيلة الدفع المحددة غير متاحة.'
                        );
                    }
                },
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:5',

                function ($attribute, $value, $fail) use ($isAdmin) {

                    if ($isAdmin) {
                        return;
                    }

                    $method = $this->paymentMethod();

                    if (
                        $method?->type === PaymentMethodType::Bank &&
                        empty($value)
                    ) {
                        $fail(
                            'يجب رفع إشعار التحويل البنكي.'
                        );
                    }
                },
            ],

            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:' . config('attachments.max_size_kb'),
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('payments', 'transaction_reference'),

                function ($attribute, $value, $fail) use ($isAdmin) {

                    if ($isAdmin) {
                        return;
                    }

                    $method = $this->paymentMethod();

                    if (
                        $method?->type === PaymentMethodType::Wallet &&
                        blank($value)
                    ) {
                        $fail(
                            'يجب إدخال رقم مرجع العملية (Transaction Reference).'
                        );
                    }
                },
            ],

            'note' => [
                'nullable',
                'string',
                'max:500',
            ],

            'override_reason' => [
                $isAdmin ? 'nullable' : 'prohibited',
                'string',
                'max:500',

                function ($attribute, $value, $fail) use ($isAdmin) {

                    if (! $isAdmin) {
                        return;
                    }

                    $check = $this->balanceCheck();

                    if ($check === null) {
                        return;
                    }

                    if (! $check['is_within_balance'] && blank($value)) {
                        $fail(
                            "المبلغ (يعادل {$check['amount_ils']} شيكل) أكبر من الرصيد المتبقي على الفاتورة ({$check['remaining_ils']} شيكل). " .
                                'لتسجيل تسوية إدارية تتجاوز الرصيد، يجب إدخال سبب التجاوز.'
                        );
                    }
                },
            ],

            'currency' => [
                ($isAdmin || $methodHasFixedCurrency) ? 'prohibited' : 'required',
                Rule::enum(Currency::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'invoice_id.required' => 'يجب تحديد الفاتورة.',

            'invoice_id.exists' => 'الفاتورة المحددة غير موجودة.',

            'currency.required' => 'يجب تحديد العملة.',

            'currency.enum' => 'العملة يجب أن تكون ILS أو USD.',

            'currency.prohibited' => 'عملة هذه الدفعة محددة تلقائيًا (من الفاتورة أو من وسيلة الدفع)، لا حاجة لإرسالها.',

            'amount.required' => 'يجب إدخال مبلغ الدفع.',

            'amount.numeric' => 'قيمة الدفع يجب أن تكون رقمًا.',

            'amount.min' => 'يجب أن تكون قيمة الدفع أكبر من صفر.',

            'payment_method_id.required' => 'يجب اختيار وسيلة الدفع.',

            'payment_method_id.exists' => 'وسيلة الدفع المحددة غير موجودة.',

            'payment_method_id.prohibited' => 'التعديل الإداري لا يتطلب وسيلة دفع.',

            'attachments.array' => 'صيغة المرفقات غير صحيحة.',

            'attachments.max' => 'لا يمكن رفع أكثر من 5 مرفقات.',

            'attachments.*.file' => 'كل مرفق يجب أن يكون ملفًا صالحًا.',

            'attachments.*.mimes' => 'المرفقات يجب أن تكون JPG أو JPEG أو PNG أو PDF.',

            'attachments.*.max' => 'حجم كل مرفق يجب ألا يتجاوز ' . round(config('attachments.max_size_kb') / 1024, 1) . ' ميجابايت.',
            'transaction_reference.max' => 'رقم مرجع العملية لا يجوز أن يتجاوز 100 حرف.',
            'transaction_reference.unique' => 'رقم مرجع العملية هذا مستخدم مسبقًا في دفعة أخرى.',

            'note.max' => 'الملاحظات لا يجوز أن تتجاوز 500 حرف.',

            'override_reason.prohibited' => 'سبب تجاوز الرصيد متاح فقط للأدمن.',

            'override_reason.max' => 'سبب التجاوز لا يجوز أن يتجاوز 500 حرف.',
        ];
    }
}
