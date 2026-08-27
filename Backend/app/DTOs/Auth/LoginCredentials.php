<?php

namespace App\DTOs\Auth;

final readonly class LoginCredentials
{
    public function __construct(
        public string $login,
        public string $password,
        public bool $remember = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            login: trim($data['login']),
            password: $data['password'],
            remember: (bool) ($data['remember'] ?? false),
        );
    }

    public function identifierField(): string
    {
        return filter_var($this->login, FILTER_VALIDATE_EMAIL) !== false ? 'email' : 'phone';
    }

    public function toArray(): array
    {
        return [
            $this->identifierField() => $this->login,
            'password' => $this->password,
        ];
    }
}
