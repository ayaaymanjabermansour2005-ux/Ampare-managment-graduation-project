<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneratorDiagnosticReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'operating_hours' => (float) $this->operating_hours,
            'temperature_celsius' => $this->temperature_celsius !== null ? (float) $this->temperature_celsius : null,
            'oil_level_percent' => $this->oil_level_percent !== null ? (float) $this->oil_level_percent : null,
            'load_percent' => $this->load_percent !== null ? (float) $this->load_percent : null,
            'voltage' => $this->voltage !== null ? (float) $this->voltage : null,
            'frequency_hz' => $this->frequency_hz !== null ? (float) $this->frequency_hz : null,
            'smoke_level' => $this->smoke_level,
            'vibration_level' => $this->vibration_level,
            'notes' => $this->notes,
            'reading_date' => $this->reading_date?->toDateString(),
            'recorded_by_name' => $this->whenLoaded('recordedBy', fn () => $this->recordedBy?->name),
            'has_analysis' => $this->whenLoaded('faultPrediction', fn () => $this->faultPrediction !== null),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
