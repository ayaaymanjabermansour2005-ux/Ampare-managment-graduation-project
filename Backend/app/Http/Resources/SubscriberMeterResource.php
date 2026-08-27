<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberMeterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meter_number' => $this->meter_number,
            'property_label' => $this->property_label,
            'status' => $this->status,
            'subscriptions_count' => $this->whenCounted('subscriptions'),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
