<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'meter_reading_id',
        'service_request_id',
        'amount',
        'discount_amount',
        'discount_id',
        'final_amount',
        'currency',
        'exchange_rate',
        'final_amount_ils',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'currency' => Currency::class,
            'exchange_rate' => 'decimal:4',
            'final_amount_ils' => 'decimal:2',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(SubscriptionServiceRequest::class, 'service_request_id');
    }

    public function appliedOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'discount_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(PlatformCommission::class);
    }

    public function complaints(): MorphMany
    {
        return $this->morphMany(Complaint::class, 'complainable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'final_amount', 'currency', 'final_amount_ils', 'due_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
