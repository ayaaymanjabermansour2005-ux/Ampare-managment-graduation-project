<?php

namespace App\Actions\Payment;

use App\DTOs\Payment\ResubmitPaymentData;
use App\Enums\PaymentStatus;
use App\Events\PaymentSubmitted;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\ExchangeRateService;
use App\Support\Eloquent\FreshOrFail;
use App\Support\Money;
use App\Support\Payment\InvoiceBalanceValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ResubmitPaymentAction
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly InvoiceBalanceValidator $balanceValidator,
    ) {}

    public function execute(Payment $payment, ResubmitPaymentData $data, User $user): Payment
    {
        return DB::transaction(function () use ($payment, $data, $user) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::NeedsCorrection) {
                throw ValidationException::withMessages([
                    'status' => ['هذه الدفعة ليست بحاجة لتعديل.'],
                ]);
            }

            $newAmount = $data->amount ?? (float) $payment->amount;

            $amountChanged = ! Money::equals($newAmount, (float) $payment->amount, 2);

            if ($amountChanged) {
                [$exchangeRate, $amountIls] = $this->exchangeRateService->toIls($newAmount, $payment->currency);
            } else {
                $exchangeRate = $payment->exchange_rate;
                $amountIls = $payment->amount_ils;
            }

            if ($amountChanged) {
                $invoice = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);

                if (! $this->balanceValidator->isWithinRemainingBalance($invoice, $amountIls, excludingPaymentId: $payment->id)) {
                    $remainingIls = $this->balanceValidator->remainingIls($invoice, excludingPaymentId: $payment->id);

                    throw ValidationException::withMessages([
                        'amount' => ["المبلغ (يعادل {$amountIls} شيكل) أكبر من الرصيد المتبقي على الفاتورة ({$remainingIls} شيكل)."],
                    ]);
                }
            }
            $payment->forceFill([
                'amount' => $newAmount,
                'exchange_rate' => $exchangeRate,
                'amount_ils' => $amountIls,
                'transaction_reference' => $data->transactionReference ?? $payment->transaction_reference,
                'note' => $data->note ?? $payment->note,
                'status' => PaymentStatus::Pending,
            ])->save();

            if (! empty($data->attachments)) {
                $this->attachmentService->storeMany($payment, $data->attachments, $user);
            }

            PaymentSubmitted::dispatch(
                FreshOrFail::reload($payment, ['invoice.subscription.generator', 'paymentMethod'])
            );

            return FreshOrFail::reload($payment, ['invoice', 'paymentMethod', 'attachments.uploader']);
        });
    }
}
