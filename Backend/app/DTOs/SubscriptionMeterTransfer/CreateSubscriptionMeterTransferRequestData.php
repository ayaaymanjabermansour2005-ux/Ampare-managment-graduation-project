<?php

namespace App\DTOs\SubscriptionMeterTransfer;

final readonly class CreateSubscriptionMeterTransferRequestData
{
    public function __construct(
        public int $subscriptionId,
        public int $toSubscriberMeterId,
        public ?string $reason,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionId: (int) $data['subscription_id'],
            toSubscriberMeterId: (int) $data['to_subscriber_meter_id'],
            reason: $data['reason'] ?? null,
        );
    }
}
