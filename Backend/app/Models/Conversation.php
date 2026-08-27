<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user1_id',
        'user2_id',
        'user1_deleted_at',
        'user2_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'user1_deleted_at' => 'datetime',
            'user2_deleted_at' => 'datetime',
        ];
    }

    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latestOfMany();
    }

    public function fault(): HasOne
    {
        return $this->hasOne(Fault::class);
    }

    public function complaint(): HasOne
    {
        return $this->hasOne(Complaint::class);
    }

    public function hasParticipant(User $user): bool
    {
        return $this->user1_id === $user->id || $this->user2_id === $user->id;
    }

    public function otherParticipant(User $user): ?User
    {
        if ($this->user1_id === $user->id) {
            return $this->user2 ?? $this->user2()->first();
        }

        if ($this->user2_id === $user->id) {
            return $this->user1 ?? $this->user1()->first();
        }

        return null;
    }

    public function isDeletedFor(User $user): bool
    {
        if ($this->user1_id === $user->id) {
            return $this->user1_deleted_at !== null;
        }

        if ($this->user2_id === $user->id) {
            return $this->user2_deleted_at !== null;
        }

        return false;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where(fn (Builder $q2) => $q2->where('user1_id', $user->id)->whereNull('user1_deleted_at'))
                ->orWhere(fn (Builder $q2) => $q2->where('user2_id', $user->id)->whereNull('user2_deleted_at'));
        });
    }
}
