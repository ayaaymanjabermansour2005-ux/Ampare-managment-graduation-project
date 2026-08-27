<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'generator_id' => $this->generator_id,
            'generator_name' => $this->whenLoaded('generator', fn () => $this->generator->name),
            'technician' => $this->whenLoaded('technician', fn () => new TechnicianResource($this->technician)),
            'requested_by' => $this->requested_by,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'instructions' => $this->instructions,
            'completion_notes' => $this->completion_notes,
            'rejection_reason' => $this->rejection_reason,
            'rating' => $this->whenLoaded('rating', fn () => $this->rating ? [
                'rating' => $this->rating->rating,
                'comment' => $this->rating->comment,
            ] : null),
            'reviewer_role' => $this->reviewer_role?->value,
            'admin_override_reason' => $this->admin_override_reason,
            'assigned_at' => $this->assigned_at,
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
        ];
    }
}
