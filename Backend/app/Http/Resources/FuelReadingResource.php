<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FuelReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tank_level_liters' => (float) $this->tank_level_liters,
            'meter_hours' => $this->meter_hours !== null ? (float) $this->meter_hours : null,
            'reading_date' => $this->reading_date?->toDateString(),
            'notes' => $this->notes,
            'recorded_by_name' => $this->whenLoaded('recorder', fn () => $this->recorder?->name),
            'attachments_count' => $this->whenCounted('attachments'),
        ];
    }
}
