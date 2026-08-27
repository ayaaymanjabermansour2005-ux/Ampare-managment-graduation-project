<?php

namespace App\Actions\Payment;

use App\DTOs\Payment\ProcessGatewayPaymentData;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentSource;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\FakePaymentGatewayService;
use App\Services\InvoiceService;
use App\Support\Payment\InvoiceBalanceValidator;
use App\Support\Payment\PaymentReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProcessGatewayPaymentAction
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly PaymentReferenceGenerator $referenceGenerator,
        private readonly InvoiceBalanceValidator $balanceValidator,
        private readonly FakePaymentGatewayService $gateway,
    ) {}

    public function execute(ProcessGatewayPaymentData $data, User $user): Payment
    {
        if (app()->environment('production')) {
            throw ValidationException::withMessages([
                'card_number' => ['بوابة الدفع الإلكتروني التجريبية غير متاحة — يرجى استخدام طريقة دفع أخرى (نقدًا أو تحويل بنكي) حتى يتم ربط بوابة دفع حقيقية.'],
            ]);
        }

        $invoice = Invoice::with([
            'subscription.subscriberMeter.subscriber',
            'subscription.generator',
        ])->findOrFail($data->invoiceId);

        $this->assertInvoiceBelongsToUser($invoice, $user);
        $this->assertInvoiceIsPayable($invoice);

        [$exchangeRate, $amountIls] = $this->exchangeRateService->toIls($data->amount, $invoice->currency);

        $this->assertWithinRemainingBalance($invoice, $amountIls);

        $result = $this->gateway->charge(
            $data->cardNumber,
            $data->expiryMonth,
            $data->expiryYear,
            $data->cvv
        );

        if (! $result['success']) {
            throw ValidationException::withMessages([
                'card_number' => [$result['decline_reason']],
            ]);
        }

        return DB::transaction(function () use ($invoice, $user, $data, $exchangeRate, $amountIls, $result) {
            $lockedInvoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            $this->assertWithinRemainingBalance($lockedInvoice, $amountIls);

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_method_id' => null,
                'source' => PaymentSource::Gateway,
                'amount' => $data->amount,
                'currency' => $invoice->currency,
                'exchange_rate' => $exchangeRate,
                'amount_ils' => $amountIls,
                'transaction_reference' => $result['transaction_reference'],
                'note' => 'دفعة عبر بوابة الدفع الإلكتروني.',
            ]);

            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'processed_by' => $user->id,
            ])->save();

            $this->invoiceService->recalculateStatus($lockedInvoice);

            return $payment->fresh(['invoice']);
        });
    }

    private function assertInvoiceBelongsToUser(Invoice $invoice, User $user): void
    {
        $subscriber = $invoice->subscription->subscriberMeter->subscriber;

        if ($subscriber->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'invoice_id' => ['لا يمكنك الدفع لهذه الفاتورة.'],
            ]);
        }
    }

    private function assertInvoiceIsPayable(Invoice $invoice): void
    {
        if (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'invoice_id' => ['لا يمكن الدفع لهذه الفاتورة.'],
            ]);
        }
    }

    private function assertWithinRemainingBalance(Invoice $invoice, float $amountIls): void
    {
        if ($this->balanceValidator->isWithinRemainingBalance($invoice, $amountIls)) {
            return;
        }

        $remainingIls = $this->balanceValidator->remainingIls($invoice);

        throw ValidationException::withMessages([
            'amount' => ["المبلغ (يعادل {$amountIls} شيكل) أكبر من المتبقي على الفاتورة ({$remainingIls} شيكل)."],
        ]);
    }
}
