<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\ServiceRequestEventType;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubscriptionServiceRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'subscription_id',
        'requested_by',
        'request_type',
        'event_type',
        'description',
        'extra_capacity_kw',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => ServiceRequestType::class,
            'event_type' => ServiceRequestEventType::class,
            'extra_capacity_kw' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => ServiceRequestStatus::class,
            'reviewed_at' => 'datetime',
            'fee_amount' => 'decimal:2',
            'fee_currency' => Currency::class,
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function override(): HasOne
    {
        return $this->hasOne(SubscriptionOverride::class, 'service_request_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'service_request_id');
    }

    public function isPending(): bool
    {
        return $this->status === ServiceRequestStatus::Pending;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'fee_amount', 'reviewed_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
