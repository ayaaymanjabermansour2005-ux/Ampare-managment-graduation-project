<?php

namespace App\Models;

use App\Enums\ArticleCommentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'name',
        'email',
        'comment',
        'status',
        'ip_address',
        'reviewed_by',
        'reviewed_at',
        'admin_reply',
        'replied_by',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ArticleCommentStatus::class,
            'reviewed_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
