<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OwnerRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'owner_id' => $this->owner_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'rated_by' => $this->whenLoaded('rater', fn () => [
                'id' => $this->rater->id,
                'name' => $this->rater->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
