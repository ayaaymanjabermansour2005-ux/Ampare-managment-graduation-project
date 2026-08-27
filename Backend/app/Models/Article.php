<?php

namespace App\Models;

use App\Enums\ArticleCommentStatus;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasAttachments;
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'title_en',
        'excerpt_en',
        'content_en',
        'cover_image_url',
        'author_id',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ArticleComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->where('status', ArticleCommentStatus::Approved->value)->latest();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ArticleRating::class);
    }

    public static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = strtolower($base.'-'.now()->format('ymd').'-'.Str::random(4));

        while (static::where('slug', $slug)->exists()) {
            $slug = strtolower($base.'-'.now()->format('ymd').'-'.Str::random(4));
        }

        return $slug;
    }
}
