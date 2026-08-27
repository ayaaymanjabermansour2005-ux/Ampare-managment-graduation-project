<?php

namespace App\DTOs\Payment;

use Illuminate\Http\UploadedFile;

final readonly class ResubmitPaymentData
{
    /**
     * @param  UploadedFile[]  $attachments
     */
    public function __construct(
        public ?float $amount,
        public ?string $transactionReference,
        public ?string $note,
        public array $attachments = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            transactionReference: $data['transaction_reference'] ?? null,
            note: $data['note'] ?? null,
            attachments: $data['attachments'] ?? [],
        );
    }
}
