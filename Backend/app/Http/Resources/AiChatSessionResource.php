<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiChatSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'context_type' => $this->context_type?->value,
            'context_type_label' => $this->context_type?->label(),
            'generator' => [
                'id' => $this->generator?->id,
                'name' => $this->generator?->name,
            ],
            'messages' => AiChatMessageResource::collection($this->whenLoaded('messages')),
            'fault_prediction_id' => $this->whenLoaded('faultPrediction', fn () => $this->faultPrediction?->id),
            'fault_id' => $this->whenLoaded('fault', fn () => $this->fault?->id),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
