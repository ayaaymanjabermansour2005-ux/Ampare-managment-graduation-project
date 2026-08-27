<?php

namespace App\DTOs\TechnicianPayment;

final readonly class CreateTechnicianPaymentData
{
    public function __construct(
        public int $technicianId,
        public ?int $paymentMethodId,
        public float $amount,
        public string $currency,
        public ?string $note,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            technicianId: (int) $data['technician_id'],
            paymentMethodId: isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? 'ILS',
            note: $data['note'] ?? null,
        );
    }
}
