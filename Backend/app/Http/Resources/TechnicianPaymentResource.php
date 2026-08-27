<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'technician_id' => $this->technician_id,
            'technician_name' => $this->whenLoaded('technician', fn () => $this->technician->user?->name),
            'owner_id' => $this->owner_id,
            'owner_name' => $this->whenLoaded('owner', fn () => $this->owner?->name),
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => $this->paymentMethod ? [
                'id' => $this->paymentMethod->id,
                'type' => $this->paymentMethod->type->value,
            ] : null),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'note' => $this->note,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'reviewed_by' => $this->reviewed_by,
            'reviewed_by_name' => $this->whenLoaded('reviewedBy', fn () => $this->reviewedBy?->name),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
