<?php

namespace App\DTOs\Payment;

final readonly class ProcessGatewayPaymentData
{
    public function __construct(
        public int $invoiceId,
        public float $amount,
        public string $cardNumber,
        public string $cardHolderName,
        public string $expiryMonth,
        public string $expiryYear,
        public string $cvv,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId: (int) $data['invoice_id'],
            amount: (float) $data['amount'],
            cardNumber: (string) $data['card_number'],
            cardHolderName: (string) $data['card_holder_name'],
            expiryMonth: (string) $data['expiry_month'],
            expiryYear: (string) $data['expiry_year'],
            cvv: (string) $data['cvv'],
        );
    }
}
