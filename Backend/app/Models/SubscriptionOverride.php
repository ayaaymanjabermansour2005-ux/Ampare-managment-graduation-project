<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'service_request_id',
        'extra_capacity_kw',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'extra_capacity_kw' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(SubscriptionServiceRequest::class, 'service_request_id');
    }

    public function isActive(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
    }
}
