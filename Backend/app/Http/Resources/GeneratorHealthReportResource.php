<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneratorHealthReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_start' => $this->period_start->toDateString(),
            'period_end' => $this->period_end->toDateString(),
            'risk_level' => $this->risk_level->value,
            'risk_level_label' => $this->risk_level->label(),
            'summary' => $this->summary,
            'recommendation' => $this->recommendation,
        ];
    }
}
