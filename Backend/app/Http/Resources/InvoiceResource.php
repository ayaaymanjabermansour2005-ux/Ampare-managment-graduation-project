<?php

namespace App\Http\Resources;

use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paidSumIls = $this->payments->where('status', PaymentStatus::Paid->value)->sum('amount_ils');

        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'meter_reading_id' => $this->meter_reading_id,
            'amount' => (float) $this->amount,
            'discount_amount' => (float) $this->discount_amount,
            'applied_offer' => $this->whenLoaded('appliedOffer', fn () => $this->appliedOffer ? [
                'id' => $this->appliedOffer->id,
                'title' => $this->appliedOffer->title,
                'discount_type' => $this->appliedOffer->discount_type,
                'discount_value' => (float) $this->appliedOffer->discount_value,
            ] : null),
            'final_amount' => (float) $this->final_amount,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate ? (float) $this->exchange_rate : null,
            'final_amount_ils' => (float) $this->final_amount_ils,
            'paid_amount_ils' => (float) $paidSumIls,
            'remaining_balance_ils' => (float) ($this->final_amount_ils - $paidSumIls),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,

            'subscriber' => $this->whenLoaded('subscription', fn () => [
                'name' => $this->subscription?->subscriberMeter?->subscriber?->user?->name,
            ]),
            'generator' => $this->whenLoaded('subscription', fn () => [
                'id' => $this->subscription?->generator?->id,
                'name' => $this->subscription?->generator?->name,
            ]),

            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'commission' => new PlatformCommissionResource($this->whenLoaded('commission')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
