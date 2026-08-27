<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'address' => $this->address,
            'beneficiary_type' => $this->beneficiary_type,
            'beneficiary_type_changed_by_name' => $this->whenLoaded(
                'beneficiaryTypeChangedBy',
                fn () => $this->beneficiaryTypeChangedBy?->name
            ),
            'beneficiary_type_changed_at' => $this->beneficiary_type_changed_at?->toDateTimeString(),
            'joined_at' => $this->joined_at?->toDateTimeString(),
        ];
    }
}
