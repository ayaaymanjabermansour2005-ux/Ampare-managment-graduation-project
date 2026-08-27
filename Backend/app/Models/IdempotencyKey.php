<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdempotencyKey extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'key',
        'user_id',
        'route',
        'response_status',
        'response_body',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'response_status' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
