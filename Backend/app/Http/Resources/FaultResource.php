<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'generator_id' => $this->generator_id,
            'generator_name' => $this->whenLoaded('generator', fn () => $this->generator->name),
            'fault_prediction_id' => $this->fault_prediction_id,
            'source' => $this->source,
            'reported_by' => $this->reported_by,
            'reported_by_name' => $this->whenLoaded('reporter', fn () => $this->reporter?->name),
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'reported_at' => $this->reported_at?->toDateTimeString(),

            'verified_by' => $this->verified_by,
            'verified_by_name' => $this->whenLoaded('verifiedBy', fn () => $this->verifiedBy?->name),
            'verified_at' => $this->verified_at?->toDateTimeString(),

            'repair_method' => $this->repair_method,
            'resolved_at' => $this->resolved_at?->toDateTimeString(),

            'closed_by' => $this->closed_by,
            'closed_by_name' => $this->whenLoaded('closedBy', fn () => $this->closedBy?->name),
            'closed_at' => $this->closed_at?->toDateTimeString(),
            'admin_override_reason' => $this->admin_override_reason,

            'technician_tasks' => TechnicianTaskResource::collection(
                $this->whenLoaded('technicianTasks')
            ),
        ];
    }
}
