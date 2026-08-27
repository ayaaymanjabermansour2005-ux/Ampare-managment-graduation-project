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
            'status' => $this->status,
            'submitted_by' => [
                'id' => $this->submitter?->id,
                'name' => $this->submitter?->name,
            ],
            'complainable' => $this->when($this->complainable_type, fn () => [
                'type' => class_basename($this->complainable_type),
                'id' => $this->complainable_id,
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
