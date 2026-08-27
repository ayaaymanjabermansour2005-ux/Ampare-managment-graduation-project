<?php

namespace App\DTOs\SubscriptionServiceRequest;

final readonly class CreateServiceRequestData
{
    public function __construct(
        public int $subscriptionId,
        public string $requestType,
        public ?string $eventType,
        public string $description,
        public ?float $extraCapacityKw,
        public string $startsAt,
        public string $endsAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionId: (int) $data['subscription_id'],
            requestType: $data['request_type'],
            eventType: $data['event_type'] ?? null,
            description: $data['description'],
            extraCapacityKw: isset($data['extra_capacity_kw']) ? (float) $data['extra_capacity_kw'] : null,
            startsAt: $data['starts_at'],
            endsAt: $data['ends_at'],
        );
    }
}
