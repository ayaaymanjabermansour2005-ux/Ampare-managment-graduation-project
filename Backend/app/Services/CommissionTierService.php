<?php

namespace App\Services;

use App\Models\CommissionTier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionTierService
{
    public function list(): Collection
    {
        return CommissionTier::query()->orderBy('min_generators_count')->get();
    }

    public function create(array $data): CommissionTier
    {
        return DB::transaction(function () use ($data) {
            $this->assertNoOverlap($data['min_generators_count'], $data['max_generators_count'] ?? null);

            return CommissionTier::create($data);
        });
    }

    public function update(CommissionTier $tier, array $data): CommissionTier
    {
        return DB::transaction(function () use ($tier, $data) {
            $this->assertNoOverlap(
                $data['min_generators_count'] ?? $tier->min_generators_count,
                $data['max_generators_count'] ?? $tier->max_generators_count,
                excludeId: $tier->id
            );

            $tier->update($data);

            return $tier->fresh();
        });
    }

    public function delete(CommissionTier $tier): void
    {
        $tier->delete();
    }

    private function assertNoOverlap(int $min, ?int $max, ?int $excludeId = null): void
    {
        $query = CommissionTier::query()
            ->where('is_active', true)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($min, $max) {
                $q->where('min_generators_count', '<=', $max ?? PHP_INT_MAX)
                    ->where(function ($q2) use ($min) {
                        $q2->whereNull('max_generators_count')
                            ->orWhere('max_generators_count', '>=', $min);
                    });
            });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'min_generators_count' => ['هذا النطاق يتداخل مع شريحة عمولة أخرى موجودة.'],
            ]);
        }
    }
}
