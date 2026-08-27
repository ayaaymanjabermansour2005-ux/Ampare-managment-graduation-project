<?php

namespace App\DTOs\TechnicianTask;

final readonly class ReviewTechnicianTaskData
{
    /**
     * @param  string  $decision  'approved' | 'rejected'
     */
    public function __construct(
        public string $decision,
        public ?string $rejectionReason,
        public ?string $adminOverrideReason,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            decision: $data['decision'],
            rejectionReason: $data['rejection_reason'] ?? null,
            adminOverrideReason: $data['admin_override_reason'] ?? null,
        );
    }
}
