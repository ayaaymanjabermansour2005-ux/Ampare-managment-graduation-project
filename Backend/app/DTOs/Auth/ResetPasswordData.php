<?php

namespace App\DTOs\Auth;

final readonly class ResetPasswordData
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
            email: $data['email'],
            password: $data['password'],
        );
    }

    public function toBrokerCredentials(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'token' => $this->token,
        ];
    }
}
