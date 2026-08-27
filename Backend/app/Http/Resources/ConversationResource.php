<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();

        $other = $currentUser && $this->resource->relationLoaded('user1') && $this->resource->relationLoaded('user2')
            ? $this->otherParticipant($currentUser)
            : null;

        return [
            'id' => $this->id,
            'other_participant' => $other ? [
                'id' => $other->id,
                'name' => $other->name,
            ] : null,
            'latest_message' => $this->whenLoaded('messages', fn () => $this->messages->first() ? [
                'text' => $this->messages->first()->message_text,
                'sender_id' => $this->messages->first()->sender_id,
                'created_at' => $this->messages->first()->created_at?->toDateTimeString(),
            ] : null),
            'unread_count' => $this->whenCounted('unread_count'),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
