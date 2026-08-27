<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionMeterTransferRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'generator_name' => $this->whenLoaded('subscription', fn () => $this->subscription->generator?->name),
            'subscriber_name' => $this->whenLoaded('subscription', fn () => $this->subscription->subscriberMeter?->subscriber?->user?->name),
            'from_meter' => $this->whenLoaded('fromMeter', fn () => $this->fromMeter ? [
                'id' => $this->fromMeter->id,
                'meter_number' => $this->fromMeter->meter_number,
            ] : null),
            'to_meter' => $this->whenLoaded('toMeter', fn () => $this->toMeter ? [
                'id' => $this->toMeter->id,
                'meter_number' => $this->toMeter->meter_number,
            ] : null),
            'requested_by' => $this->requested_by,
            'requested_by_name' => $this->whenLoaded('requestedBy', fn () => $this->requestedBy?->name),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'reason' => $this->reason,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_by_name' => $this->whenLoaded('reviewedBy', fn () => $this->reviewedBy?->name),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
