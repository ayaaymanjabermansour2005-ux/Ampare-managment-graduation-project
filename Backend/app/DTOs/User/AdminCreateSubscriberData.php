<?php

namespace App\DTOs\User;

final readonly class AdminCreateSubscriberData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $password,
        public ?string $address,
        public ?int $neighborhoodId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            password: $data['password'],
            address: $data['address'] ?? null,
            neighborhoodId: $data['neighborhood_id'] ?? null,
        );
    }
}
