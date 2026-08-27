<?php

namespace App\Http\Resources;

use App\Enums\OfferTargetMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'target_mode' => $this->target_mode,
            'beneficiary_type' => $this->beneficiary_type,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'owner' => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
            ],
            'targeted_subscribers' => $this->when(
                $this->target_mode === OfferTargetMode::Selected && $this->relationLoaded('targetedSubscribers'),
                fn () => $this->targetedSubscribers->pluck('id')
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
