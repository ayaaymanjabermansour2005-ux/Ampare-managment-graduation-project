<?php

namespace App\DTOs\TechnicianRating;

final readonly class CreateTechnicianRatingData
{
    public function __construct(
        public int $rating,
        public ?string $comment,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            rating: (int) $data['rating'],
            comment: $data['comment'] ?? null,
        );
    }
}
