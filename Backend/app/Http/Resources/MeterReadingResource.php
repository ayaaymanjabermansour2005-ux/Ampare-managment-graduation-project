<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeterReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'reading_date' => $this->reading_date?->toDateString(),
            'previous_reading' => (float) $this->previous_reading,
            'current_reading' => (float) $this->current_reading,
            'consumed_kw' => (float) $this->consumed_kw,
            'reading_warning' => $this->reading_warning ?? null,

            'subscriber' => $this->whenLoaded('subscription', fn () => [
                'name' => $this->subscription?->subscriberMeter?->subscriber?->user?->name,
            ]),
            'generator' => $this->whenLoaded('subscription', fn () => [
                'id' => $this->subscription?->generator?->id,
                'name' => $this->subscription?->generator?->name,
            ]),

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'approved_by' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,

            'created_by' => $this->creator?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
