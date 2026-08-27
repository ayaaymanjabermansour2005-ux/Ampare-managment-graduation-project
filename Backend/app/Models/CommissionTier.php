<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_generators_count',
        'max_generators_count',
        'commission_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_generators_count' => 'integer',
            'max_generators_count' => 'integer',
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function matches(int $generatorsCount): bool
    {
        if ($generatorsCount < $this->min_generators_count) {
            return false;
        }

        return $this->max_generators_count === null
            || $generatorsCount <= $this->max_generators_count;
    }
}
