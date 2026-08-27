<?php

namespace App\DTOs\SubscriptionServiceRequest;

final readonly class ReviewServiceRequestData
{
    /**
     * @param  string  $decision  'approved' | 'rejected'
     */
    public function __construct(
        public string $decision,
        public ?string $reviewNote,
        public ?float $feeAmount,
        public ?string $feeCurrency,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            decision: $data['decision'],
            reviewNote: $data['review_note'] ?? null,
            feeAmount: isset($data['fee_amount']) ? (float) $data['fee_amount'] : null,
            feeCurrency: $data['fee_currency'] ?? null,
        );
    }
}
