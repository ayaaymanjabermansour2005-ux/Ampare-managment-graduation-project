<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticleService
{
    public function listForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Article::with('author')->latest()->paginate($perPage);
    }

    public function listPublished(int $perPage = 12): LengthAwarePaginator
    {
        return Article::where('is_published', true)
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function showPublished(string $slug): Article
    {
        return Article::where('slug', $slug)->where('is_published', true)->with('attachments')->firstOrFail();
    }

    public function create(array $data, User $author): Article
    {
        return Article::create([
            'title' => $data['title'],
            'slug' => Article::generateUniqueSlug($data['title']),
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'title_en' => $data['title_en'],
            'excerpt_en' => $data['excerpt_en'] ?? null,
            'content_en' => $data['content_en'],
            'cover_image_url' => $data['cover_image_url'] ?? null,
            'author_id' => $author->id,
            'is_published' => $data['is_published'] ?? false,
            'published_at' => ($data['is_published'] ?? false) ? now() : null,
        ]);
    }

    public function update(Article $article, array $data): Article
    {
        $wasPublished = $article->is_published;
        $article->update($data);

        if (! $wasPublished && $article->is_published) {
            $article->update(['published_at' => now()]);
        }

        return $article->fresh();
    }

    public function delete(Article $article): void
    {
        $article->delete();
    }
}
