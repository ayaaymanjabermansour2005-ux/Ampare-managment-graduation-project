<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => [
                'id' => $this->sender?->id,
                'name' => $this->sender?->name,
            ],
            'message_text' => $this->message_text,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'is_read' => $this->is_read,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
