<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Enums\OperatingSchedule;
use App\Enums\SubscriptionStatus;
use App\Traits\HasAttachments;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Subscription extends Model
{
    use HasAttachments, HasFactory, LogsActivity, SoftDeletes;

    public const CAPACITY_RESERVING_STATUSES = ['active'];

    public const DUPLICATE_BLOCKING_STATUSES = ['pending', 'active'];


    protected $fillable = [
        'subscriber_meter_id',
        'generator_id',
        'agreed_price_per_kw',
        'currency',
        'requested_capacity_kw',
        'schedule',
        'billing_cycle',
        'service_start_time',
        'service_end_time',
        'contract_type',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'agreed_price_per_kw' => 'decimal:2',
            'currency' => Currency::class,
            'requested_capacity_kw' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'schedule' => OperatingSchedule::class,
            'billing_cycle' => BillingCycle::class,
            'status' => SubscriptionStatus::class,
        ];
    }

    public static function scheduleValues(): array
    {
        return array_column(OperatingSchedule::cases(), 'value');
    }

    public static function statusValues(): array
    {
        return array_column(SubscriptionStatus::cases(), 'value');
    }

    public function subscriberMeter(): BelongsTo
    {
        return $this->belongsTo(SubscriberMeter::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }

    public function owner(): ?User
    {
        return $this->generator?->owner;
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function nextReadingDueDate(): ?Carbon
    {
        $lastReadingDate = $this->meterReadings()->max('reading_date');
        $baseDate = $lastReadingDate ? Carbon::parse($lastReadingDate) : $this->start_date;

        if (! $baseDate) {
            return null;
        }

        return $baseDate->copy()->addDays($this->billing_cycle->intervalDays());
    }

    public function isDueForReading(): bool
    {
        $due = $this->nextReadingDueDate();

        return $due !== null && $due->isPast();
    }

    public function complaints(): MorphMany
    {
        return $this->morphMany(Complaint::class, 'complainable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'agreed_price_per_kw', 'currency', 'requested_capacity_kw'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
