<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformCommissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [];
        }

        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'commission_rate' => (float) $this->commission_rate,
            'commission_amount' => (float) $this->commission_amount,
            'status' => $this->status,
            'earned_at' => $this->earned_at?->toDateTimeString(),
            'paid_at' => $this->paid_at?->toDateTimeString(),

            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
            ]),
            'generator_name' => $this->whenLoaded(
                'invoice',
                fn () => $this->invoice?->subscription?->generator?->name
            ),
        ];
    }
}
