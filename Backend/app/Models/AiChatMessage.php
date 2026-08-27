<?php

namespace App\Models;

use App\Enums\AiChatMessageRole;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    use HasAttachments, HasFactory;

    protected $fillable = [
        'session_id',
        'role',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'role' => AiChatMessageRole::class,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }
}
