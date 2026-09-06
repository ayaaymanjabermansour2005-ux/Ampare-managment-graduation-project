<?php

namespace App\DTOs\Complaint;

use App\Enums\ComplaintChannel;
use App\Enums\ComplaintPriority;

final readonly class CreateComplaintData
{
    public function __construct(
        public ?string $complainableType,
        public ?int $complainableId,
        public string $subject,
        public string $description,
        public ComplaintChannel $channel,
        public ComplaintPriority $priority,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            complainableType: $data['complainable_type'] ?? null,
            complainableId: isset($data['complainable_id']) ? (int) $data['complainable_id'] : null,
            subject: $data['subject'],
            description: $data['description'],
            channel: isset($data['channel']) ? ComplaintChannel::from($data['channel']) : ComplaintChannel::App,
            priority: isset($data['priority']) ? ComplaintPriority::from($data['priority']) : ComplaintPriority::Medium,
        );
    }
}
