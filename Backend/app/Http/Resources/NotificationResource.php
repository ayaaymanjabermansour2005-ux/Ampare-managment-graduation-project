<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => class_basename($this->type),
            'title' => $this->data['title'] ?? null,
            'message' => $this->data['message'] ?? null,
            'link_type' => $this->data['link_type'] ?? null,
            'link_id' => $this->data['link_id'] ?? null,
            'is_read' => ! is_null($this->read_at),
            'read_at' => $this->read_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
