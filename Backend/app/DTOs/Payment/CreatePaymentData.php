<?php

namespace App\DTOs\Payment;

use Illuminate\Http\UploadedFile;

final readonly class CreatePaymentData
{
    /**
     * @param  UploadedFile[]  $attachments
     */
    public function __construct(
        public int $invoiceId,
        public ?int $paymentMethodId,
        public float $amount,
        public ?string $currency,
        public ?string $transactionReference,
        public ?string $note,
        public array $attachments = [],
        public ?string $overrideReason = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId: (int) $data['invoice_id'],
            paymentMethodId: isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? null,
            transactionReference: $data['transaction_reference'] ?? null,
            note: $data['note'] ?? null,
            attachments: $data['attachments'] ?? [],
            overrideReason: $data['override_reason'] ?? null,
        );
    }
}
