<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_type' => $this->request_type,
            'is_high_priority' => (bool) $this->request_type?->isHighPriority(),
            'event_type' => $this->event_type,
            'description' => $this->description,
            'extra_capacity_kw' => $this->extra_capacity_kw !== null ? (float) $this->extra_capacity_kw : null,
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'ends_at' => $this->ends_at?->toDateTimeString(),
            'status' => $this->status,
            'review_note' => $this->review_note,
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'reviewed_by_name' => $this->whenLoaded('reviewedBy', fn() => $this->reviewedBy?->name),
            'fee_amount' => $this->fee_amount !== null ? (float) $this->fee_amount : null,
            'fee_currency' => $this->fee_currency,
            'override_active' => $this->whenLoaded('override', fn() => $this->override?->isActive() ?? false),
            'invoice' => $this->whenLoaded('invoice', fn() => $this->invoice ? [
                'id' => $this->invoice->id,
                'final_amount' => (float) $this->invoice->final_amount,
                'currency' => $this->invoice->currency,
                'status' => $this->invoice->status,
                'due_date' => $this->invoice->due_date?->toDateString(),
            ] : null),
            'subscription' => [
                'id' => $this->subscription?->id,
                'generator_id' => $this->subscription?->generator?->id,
                'generator_name' => $this->subscription?->generator?->name,
            ],
            'requested_by_name' => $this->whenLoaded('requestedBy', fn() => $this->requestedBy?->name),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
