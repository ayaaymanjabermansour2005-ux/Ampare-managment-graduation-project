<?php

namespace App\DTOs\Auth;

final readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone,
        public int $neighborhoodId,
        public string $address,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'] ?? null,
            neighborhoodId: (int) $data['neighborhood_id'],
            address: $data['address'],
        );
    }
}
