<?php

namespace App\Models;

use App\Enums\AiChatContextType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'generator_id',
        'title',
        'context_type',
    ];

    protected function casts(): array
    {
        return [
            'context_type' => AiChatContextType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'session_id')->oldest();
    }

    public function faultPrediction(): HasOne
    {
        return $this->hasOne(FaultPrediction::class, 'ai_chat_session_id');
    }

    public function fault(): HasOne
    {
        return $this->hasOne(Fault::class, 'ai_chat_session_id');
    }

    public function isOwnerDiagnostic(): bool
    {
        return $this->context_type === AiChatContextType::OwnerDiagnostic;
    }

    public function isSubscriberSupport(): bool
    {
        return $this->context_type === AiChatContextType::SubscriberSupport;
    }
}
