<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'payment_method_id' => $this->payment_method_id,
            'payment_method_type' => $this->paymentMethod?->type,
            'source' => $this->source,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate ? (float) $this->exchange_rate : null,
            'amount_ils' => (float) $this->amount_ils,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'attachments_count' => $this->whenCounted('attachments'),
            'note' => $this->note,
            'status' => $this->status,

            'subscriber' => $this->whenLoaded('invoice', fn() => [
                'name' => $this->invoice?->subscription?->subscriberMeter?->subscriber?->user?->name,
                'phone' => $this->invoice?->subscription?->subscriberMeter?->subscriber?->user?->phone,
            ]),
            'generator' => $this->whenLoaded('invoice', fn() => [
                'id' => $this->invoice?->subscription?->generator?->id,
                'name' => $this->invoice?->subscription?->generator?->name,
            ]),

            'processed_by' => $this->whenLoaded('processedBy', fn() => [
                'id' => $this->processedBy?->id,
                'name' => $this->processedBy?->name,
            ]),
            'paid_at' => $this->paid_at?->toDateTimeString(),

            'rejection_reason' => $this->rejection_reason,

            'reviewed_by' => $this->whenLoaded('reviewedBy', fn() => [
                'id' => $this->reviewedBy?->id,
                'name' => $this->reviewedBy?->name,
            ]),
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'review_note' => $this->review_note,

            'reviews' => $this->whenLoaded('reviews', fn() => $this->reviews->map(fn($review) => [
                'id' => $review->id,
                'status' => $review->status,
                'reason' => $review->reason,
                'reviewed_by' => $review->reviewer?->name,
                'created_at' => $review->created_at?->toDateTimeString(),
            ])),

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
