<?php

namespace App\Http\Resources;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'is_default' => (bool) $this->is_default,
            'currency' => $this->currency,
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number_masked' => PaymentMethod::maskAccountNumber($this->account_number),
            'owner' => $this->whenLoaded('owner', fn() => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
