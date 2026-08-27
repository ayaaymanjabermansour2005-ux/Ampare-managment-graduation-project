<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'rated_by_name' => $this->whenLoaded('rater', fn () => $this->rater?->name),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
