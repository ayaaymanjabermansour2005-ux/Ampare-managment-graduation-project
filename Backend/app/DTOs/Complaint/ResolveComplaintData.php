<?php

namespace App\DTOs\Complaint;

final readonly class ResolveComplaintData
{
    /**
     * @param  string  $status  'in_progress' | 'waiting_subscriber' | 'resolved'
     */
    public function __construct(
        public string $status,
        public ?string $resolutionNote,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'],
            resolutionNote: $data['resolution_note'] ?? null,
        );
    }
}
