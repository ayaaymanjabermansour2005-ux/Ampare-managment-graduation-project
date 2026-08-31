<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isTechnician = $user?->isTechnician() ?? false;
        $canSeeNotes = ($user?->isAdmin() ?? false) || ($user?->isOwner() ?? false);

        return [
            'id' => $this->id,
            'agreed_price_per_kw' => $this->when(! $isTechnician, (float) $this->agreed_price_per_kw),
            'currency' => $this->when(! $isTechnician, $this->currency),
            'requested_capacity_kw' => (float) $this->requested_capacity_kw,
            'schedule' => $this->schedule,
            'billing_cycle' => $this->billing_cycle,
            'next_reading_due_date' => $this->nextReadingDueDate()?->toDateString(),
            'is_due_for_reading' => $this->isDueForReading(),
            'service_start_time' => $this->service_start_time,
            'service_end_time' => $this->service_end_time,
            'contract_type' => $this->contract_type,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->when($canSeeNotes, $this->notes),
            'subscriber_meter' => [
                'id' => $this->subscriberMeter?->id,
                'meter_number' => $this->subscriberMeter?->meter_number,
                'property_label' => $this->subscriberMeter?->property_label,
            ],
            'subscriber' => [
                'id' => $this->subscriberMeter?->subscriber?->id,
                'user_id' => $this->subscriberMeter?->subscriber?->user_id,
                'name' => $this->subscriberMeter?->subscriber?->user?->name,
                'email' => $this->subscriberMeter?->subscriber?->user?->email,
                'phone' => $this->subscriberMeter?->subscriber?->user?->phone,
                'neighborhood' => $this->subscriberMeter?->subscriber?->neighborhood?->name,
                'beneficiary_type' => $this->subscriberMeter?->subscriber?->beneficiary_type?->value,
                'beneficiary_type_label' => $this->subscriberMeter?->subscriber?->beneficiary_type?->label(),
            ],
            'generator' => [
                'id' => $this->generator?->id,
                'name' => $this->generator?->name,
                'owner_id' => $this->generator?->owner_id,
                'owner_name' => $this->generator?->owner?->name,
            ],
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
