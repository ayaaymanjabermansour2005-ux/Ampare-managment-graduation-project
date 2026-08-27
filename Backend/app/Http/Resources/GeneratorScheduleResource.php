<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneratorScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'generator_id' => $this->generator_id,
            'generator_name' => $this->whenLoaded('generator', fn () => $this->generator?->name),
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'ends_at' => $this->ends_at?->toDateTimeString(),
            'note' => $this->note,
            'is_active_now' => $this->isActiveNow(),
            'created_by_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
        ];
    }
}
