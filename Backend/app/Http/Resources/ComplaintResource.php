<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'channel' => $this->channel,
            'priority' => $this->priority,
            'status' => $this->status,
            'sla_due_at' => $this->sla_due_at?->toDateTimeString(),
            'submitted_by' => [
                'id' => $this->submitter?->id,
                'name' => $this->submitter?->name,
            ],
            'complainable' => $this->when($this->complainable_type, fn () => [
                'type' => class_basename($this->complainable_type),
                'id' => $this->complainable_id,
            ]),
            'assigned_to' => $this->when($this->assigned_to, fn () => [
                'id' => $this->assignedTo?->id,
                'name' => $this->assignedTo?->name,
            ]),
            'resolved_by' => $this->when($this->resolved_by, fn () => [
                'id' => $this->resolver?->id,
                'name' => $this->resolver?->name,
            ]),
            'resolution_note' => $this->resolution_note,
            'resolved_at' => $this->resolved_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
