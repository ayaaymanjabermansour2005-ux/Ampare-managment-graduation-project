<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratorSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['generator_id', 'starts_at', 'ends_at', 'note', 'created_by'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActiveNow(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
    }
}
