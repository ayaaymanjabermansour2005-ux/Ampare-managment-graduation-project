<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'max_generators' => $this->max_generators,
            'price_monthly' => (float) $this->price_monthly,
            'currency' => $this->currency,
        ];
    }
}
