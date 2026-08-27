<?php

namespace App\DTOs\TechnicianTask;

use App\Models\Fault;
use App\Models\Subscription;

final readonly class CreateTechnicianTaskData
{
    public function __construct(
        public int $generatorId,
        public ?int $technicianId,
        public string $type,
        public ?string $taskableType,
        public ?int $taskableId,
        public ?string $instructions,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            generatorId: (int) $data['generator_id'],
            technicianId: ! empty($data['technician_id']) ? (int) $data['technician_id'] : null,
            type: $data['type'],
            taskableType: self::normalizeTaskableType($data['taskable_type'] ?? null),
            taskableId: isset($data['taskable_id']) ? (int) $data['taskable_id'] : null,
            instructions: $data['instructions'] ?? null,
        );
    }

    private static function normalizeTaskableType(?string $alias): ?string
    {
        return match ($alias) {
            'fault' => Fault::class,
            'subscription' => Subscription::class,
            null => null,
            default => $alias,
        };
    }
}
