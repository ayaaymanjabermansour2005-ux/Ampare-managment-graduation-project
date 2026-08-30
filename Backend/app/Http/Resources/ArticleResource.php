<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_en' => $this->title_en,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'excerpt_en' => $this->excerpt_en,
            'content' => $this->when(! $request->routeIs('articles.public-index'), $this->content),
            'content_en' => $this->when(! $request->routeIs('articles.public-index'), $this->content_en),
            'cover_image_url' => $this->cover_image_url,
            'images' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'author_name' => $this->whenLoaded('author', fn () => $this->author?->name),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toDateString(),
        ];
    }
}
