<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'min_generators_count' => $this->min_generators_count,
            'max_generators_count' => $this->max_generators_count,
            'commission_rate' => (float) $this->commission_rate,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
