<?php

namespace App\Support\Auth;


final readonly class SessionInvalidationResult
{
    private function __construct(
        public bool $supported,
        public int $deletedCount,
    ) {}

    public static function unsupported(): self
    {
        return new self(supported: false, deletedCount: 0);
    }

    public static function success(int $deletedCount): self
    {
        return new self(supported: true, deletedCount: $deletedCount);
    }
}
