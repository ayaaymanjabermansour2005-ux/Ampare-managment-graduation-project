<?php

namespace App\Models;

use App\Enums\HealthRiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratorHealthReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'generator_id',
        'period_start',
        'period_end',
        'risk_level',
        'summary',
        'recommendation',
        'input_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'risk_level' => HealthRiskLevel::class,
            'input_snapshot' => 'array',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }
}
