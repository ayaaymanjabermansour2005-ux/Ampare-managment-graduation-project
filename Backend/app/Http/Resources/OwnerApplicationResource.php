<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OwnerApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'reviewed_by' => $this->whenLoaded('reviewedBy', fn () => $this->reviewedBy ? [
                'id' => $this->reviewedBy->id,
                'name' => $this->reviewedBy->name,
            ] : null),
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'review_note' => $this->review_note,
            'created_user_id' => $this->created_user_id,
            'created_user' => $this->whenLoaded('createdUser', fn () => $this->createdUser ? [
                'id' => $this->createdUser->id,
                'name' => $this->createdUser->name,
                'email' => $this->createdUser->email,
            ] : null),

            'internal_note' => $this->internal_note,

            'generator_draft' => [
                'name' => $this->generator_name,
                'price_per_kw' => $this->generator_price_per_kw !== null ? (float) $this->generator_price_per_kw : null,
                'currency' => $this->generator_currency?->value,
                'capacity_kw' => $this->generator_capacity_kw,
                'city' => $this->generator_city,
                'neighborhood' => $this->whenLoaded('generatorNeighborhood', fn () => $this->generatorNeighborhood ? [
                    'id' => $this->generatorNeighborhood->id,
                    'name' => $this->generatorNeighborhood->name,
                ] : null),
                'address' => $this->generator_address,
                'latitude' => $this->generator_latitude !== null ? (float) $this->generator_latitude : null,
                'longitude' => $this->generator_longitude !== null ? (float) $this->generator_longitude : null,
            ],

            'documents' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($a) => [
                'id' => $a->id,
                'document_type' => $a->document_type?->value,
                'document_type_label' => $a->document_type?->label(),
                'name' => $a->original_name,
                'mime_type' => $a->mime_type,
                'url' => route('attachments.preview', $a->id),
            ])),

            'is_duplicate_email' => $this->is_duplicate_email ?? false,
            'is_duplicate_phone' => $this->is_duplicate_phone ?? false,
            'duplicate_user_id' => $this->duplicate_user_id ?? null,
            'duplicate_user_name' => $this->duplicate_user_name ?? null,
            'duplicate_user_email' => $this->duplicate_user_email ?? null,

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
