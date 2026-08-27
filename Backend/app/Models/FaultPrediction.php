<?php

namespace App\Models;

use App\Enums\FaultPredictionSource;
use App\Enums\FaultPredictionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FaultPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'generator_id',
        'source',
        'ai_chat_session_id',
        'generator_diagnostic_reading_id',
        'is_actual_fault',
        'prediction_type',
        'confidence',
        'recommendation',
        'input_snapshot',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_actual_fault' => 'boolean',
            'confidence' => 'decimal:2',
            'input_snapshot' => 'array',
            'status' => FaultPredictionStatus::class,
            'source' => FaultPredictionSource::class,
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }

    public function confirmedFault(): HasOne
    {
        return $this->hasOne(Fault::class);
    }

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_chat_session_id');
    }

    public function diagnosticReading(): BelongsTo
    {
        return $this->belongsTo(GeneratorDiagnosticReading::class, 'generator_diagnostic_reading_id');
    }
}
