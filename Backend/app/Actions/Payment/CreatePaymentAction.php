<?php

namespace App\Actions\Payment;

use App\DTOs\Payment\CreatePaymentData;
use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentReviewStatus;
use App\Enums\PaymentSource;
use App\Enums\PaymentStatus;
use App\Events\PaymentSubmitted;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\ExchangeRateService;
use App\Services\InvoiceService;
use App\Support\Payment\InvoiceBalanceValidator;
use App\Support\Payment\PaymentReferenceGenerator;
use App\Support\Payment\PaymentReviewLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreatePaymentAction
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly AttachmentService $attachmentService,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly PaymentReferenceGenerator $referenceGenerator,
        private readonly PaymentReviewLogger $reviewLogger,
        private readonly InvoiceBalanceValidator $balanceValidator,
    ) {}

    public function execute(CreatePaymentData $data, User $user): Payment
    {
        $invoice = Invoice::with([
            'subscription.subscriberMeter.subscriber',
            'subscription.generator',
        ])->findOrFail($data->invoiceId);

        $this->assertInvoiceBelongsToUser($invoice, $user);
        $this->assertInvoiceIsPayable($invoice);

        $method = $this->resolvePaymentMethod($invoice, $data, $user);
        $currency = $this->resolveCurrency($invoice, $data, $user, $method);

        [$exchangeRate, $amountIls] = $this->exchangeRateService->toIls($data->amount, $currency);

        $this->assertWithinRemainingBalanceOrOverride($invoice, $amountIls, $user, $data->overrideReason);

        return DB::transaction(function () use ($data, $invoice, $user, $method, $currency, $exchangeRate, $amountIls) {

            $lockedInvoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            $isOverride = $this->assertWithinRemainingBalanceOrOverride(
                $lockedInvoice,
                $amountIls,
                $user,
                $data->overrideReason
            );

            return $user->isAdmin()
                ? $this->createAdjustmentPayment($data, $invoice, $user, $currency, $exchangeRate, $amountIls, $isOverride)
                : $this->createSubscriberPayment($data, $invoice, $user, $method, $currency, $exchangeRate, $amountIls);
        });
    }

    private function assertInvoiceBelongsToUser(Invoice $invoice, User $user): void
    {
        if (! $user->isSubscriber()) {
            return;
        }

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

    private function resolvePaymentMethod(Invoice $invoice, CreatePaymentData $data, User $user): ?PaymentMethod
    {
        if ($user->isAdmin()) {
            return null;
        }

        $ownerId = $invoice->subscription->generator->owner_id;

        $method = PaymentMethod::whereKey($data->paymentMethodId)
            ->where('user_id', $ownerId)
            ->first();

        if (! $method) {
            throw ValidationException::withMessages([
                'payment_method_id' => ['وسيلة الدفع غير صحيحة.'],
            ]);
        }

        return $method;
    }

    private function resolveCurrency(Invoice $invoice, CreatePaymentData $data, User $user, ?PaymentMethod $method): Currency
    {
        return match (true) {
            $user->isAdmin() => $invoice->currency,
            $method->type !== PaymentMethodType::Cash => $method->currency,
            default => Currency::from($data->currency),
        };
    }

    /**
     *
     * @return bool      
     */
    private function assertWithinRemainingBalanceOrOverride(
        Invoice $invoice,
        float $amountIls,
        User $user,
        ?string $overrideReason
    ): bool {
        if ($this->balanceValidator->isWithinRemainingBalance($invoice, $amountIls)) {
            return false;
        }

        $remainingIls = $this->balanceValidator->remainingIls($invoice);

        if (! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'amount' => ["المبلغ (يعادل {$amountIls} شيكل) أكبر من المتبقي على الفاتورة ({$remainingIls} شيكل)."],
            ]);
        }

        if (blank($overrideReason)) {
            throw ValidationException::withMessages([
                'override_reason' => [
                    "المبلغ (يعادل {$amountIls} شيكل) أكبر من المتبقي على الفاتورة ({$remainingIls} شيكل). " .
                        'لتسجيل تسوية إدارية تتجاوز الرصيد، يجب توضيح سبب التجاوز.',
                ],
            ]);
        }

        return true;
    }

    private function createAdjustmentPayment(
        CreatePaymentData $data,
        Invoice $invoice,
        User $user,
        Currency $currency,
        ?float $exchangeRate,
        float $amountIls,
        bool $isOverride
    ): Payment {
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => null,
            'source' => PaymentSource::Adjustment,
            'amount' => $data->amount,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'amount_ils' => $amountIls,
            'transaction_reference' => $data->transactionReference ?? $this->referenceGenerator->generate(),
            'note' => $data->note,
        ]);

        $payment->forceFill([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'processed_by' => $user->id,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_note' => $isOverride ? $data->overrideReason : null,
        ])->save();

        if ($isOverride) {
            $this->reviewLogger->log($payment, $user, PaymentReviewStatus::Approved, $data->overrideReason);
        }

        $this->invoiceService->recalculateStatus($invoice);

        return $payment->fresh();
    }

    private function createSubscriberPayment(
        CreatePaymentData $data,
        Invoice $invoice,
        User $user,
        ?PaymentMethod $method,
        Currency $currency,
        ?float $exchangeRate,
        float $amountIls
    ): Payment {
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method?->id,
            'source' => PaymentSource::Subscriber,
            'amount' => $data->amount,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'amount_ils' => $amountIls,
            'transaction_reference' => $data->transactionReference ?? $this->referenceGenerator->generate(),
            'note' => $data->note,
        ]);

        if (! empty($data->attachments)) {
            $this->attachmentService->storeMany($payment, $data->attachments, $user);
        }

        PaymentSubmitted::dispatch(
            $payment->fresh(['invoice.subscription.generator', 'paymentMethod'])
        );

        return $payment->fresh(['invoice', 'paymentMethod', 'attachments.uploader']);
    }
}
