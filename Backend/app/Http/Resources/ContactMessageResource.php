<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'subject' => $this->subject?->value,
            'subject_label' => $this->subject?->label(),
            'message' => $this->message,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'admin_note' => $this->admin_note,
            'handled_by' => $this->whenLoaded('handledBy', fn () => $this->handledBy ? [
                'id' => $this->handledBy->id,
                'name' => $this->handledBy->name,
            ] : null),
            'handled_at' => $this->handled_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
