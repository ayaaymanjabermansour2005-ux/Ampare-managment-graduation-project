<?php

namespace App\DTOs\GeneratorSchedule;

final readonly class CreateGeneratorScheduleData
{
    public function __construct(
        public string $startsAt,
        public string $endsAt,
        public ?string $note,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            startsAt: $data['starts_at'],
            endsAt: $data['ends_at'],
            note: $data['note'] ?? null,
        );
    }
}
