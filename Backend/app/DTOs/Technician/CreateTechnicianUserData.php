<?php

namespace App\DTOs\Technician;

final readonly class CreateTechnicianUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $notes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            notes: $data['notes'] ?? null,
        );
    }
}
