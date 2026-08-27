<?php

namespace App\Models;

use App\Enums\SmokeLevel;
use App\Enums\VibrationLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GeneratorDiagnosticReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'generator_id',
        'recorded_by',
        'operating_hours',
        'temperature_celsius',
        'oil_level_percent',
        'load_percent',
        'voltage',
        'frequency_hz',
        'smoke_level',
        'vibration_level',
        'notes',
        'reading_date',
    ];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'decimal:2',
            'temperature_celsius' => 'decimal:2',
            'oil_level_percent' => 'decimal:2',
            'load_percent' => 'decimal:2',
            'voltage' => 'decimal:2',
            'frequency_hz' => 'decimal:2',
            'smoke_level' => SmokeLevel::class,
            'vibration_level' => VibrationLevel::class,
            'reading_date' => 'date',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function faultPrediction(): HasOne
    {
        return $this->hasOne(FaultPrediction::class, 'generator_diagnostic_reading_id');
    }
}
