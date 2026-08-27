<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toDateTimeString(),

            'admin_reply' => $this->admin_reply,
            'admin_reply_at' => $this->replied_at?->toDateTimeString(),

            $this->mergeWhen($request->user()?->isAdmin(), [
                'email' => $this->email,
                'status' => $this->status?->value,
                'status_label' => $this->status?->label(),
                'article' => $this->whenLoaded('article', fn () => [
                    'id' => $this->article->id,
                    'title' => $this->article->title,
                    'slug' => $this->article->slug,
                ]),
                'reviewed_by' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->name),
                'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
                'replied_by' => $this->whenLoaded('replier', fn () => $this->replier?->name),
            ]),
        ];
    }
}
