<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'description' => $this->description,
            'uploaded_by' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
            ]),
            'download_url' => route('attachments.download', $this->id),
            'preview_url' => route('attachments.preview', $this->id),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
