<?php

namespace App\Models;

use App\Enums\SubscriptionMeterTransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubscriptionMeterTransferRequest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'from_subscriber_meter_id',
        'to_subscriber_meter_id',
        'requested_by',
        'status',
        'reason',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionMeterTransferStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fromMeter(): BelongsTo
    {
        return $this->belongsTo(SubscriberMeter::class, 'from_subscriber_meter_id');
    }

    public function toMeter(): BelongsTo
    {
        return $this->belongsTo(SubscriberMeter::class, 'to_subscriber_meter_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'to_subscriber_meter_id', 'reviewed_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
