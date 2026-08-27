<?php

namespace App\DTOs\Complaint;

final readonly class CreateComplaintData
{
    public function __construct(
        public ?string $complainableType,
        public ?int $complainableId,
        public string $subject,
        public string $description,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            complainableType: $data['complainable_type'] ?? null,
            complainableId: isset($data['complainable_id']) ? (int) $data['complainable_id'] : null,
            subject: $data['subject'],
            description: $data['description'],
        );
    }
}
