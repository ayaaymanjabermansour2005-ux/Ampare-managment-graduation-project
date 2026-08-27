<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FuelPurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'liters' => (float) $this->liters,
            'cost_amount' => (float) $this->cost_amount,
            'currency' => $this->currency,
            'cost_amount_ils' => (float) $this->cost_amount_ils,
            'purchased_at' => $this->purchased_at?->toDateString(),
            'notes' => $this->notes,
            'recorded_by_name' => $this->whenLoaded('recorder', fn () => $this->recorder?->name),
            'attachments_count' => $this->whenCounted('attachments'),
        ];
    }
}
