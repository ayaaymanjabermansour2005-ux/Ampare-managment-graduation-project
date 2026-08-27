<?php

namespace App\DTOs\AiChat;

final readonly class StartAiChatSessionData
{
    public function __construct(
        public ?int $generatorId,
        public ?string $initialMessage,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            generatorId: isset($data['generator_id']) ? (int) $data['generator_id'] : null,
            initialMessage: $data['message'] ?? null,
        );
    }
}
