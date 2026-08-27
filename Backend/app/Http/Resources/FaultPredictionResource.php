<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaultPredictionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'generator_id' => $this->generator_id,
            'generator' => $this->whenLoaded('generator', fn () => [
                'id' => $this->generator->id,
                'name' => $this->generator->name,
            ]),
            'source' => $this->source?->value,
            'source_label' => $this->source?->label(),
            'ai_chat_session_id' => $this->ai_chat_session_id,
            'is_actual_fault' => $this->is_actual_fault,
            'prediction_type' => $this->prediction_type,
            'confidence' => $this->confidence !== null ? (float) $this->confidence : null,
            'recommendation' => $this->recommendation,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'confirmed_fault_id' => $this->whenLoaded('confirmedFault', fn () => $this->confirmedFault?->id),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
