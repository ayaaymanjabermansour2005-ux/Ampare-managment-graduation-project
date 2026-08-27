<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\FuelType;
use App\Enums\GeneratorStatus;
use App\Enums\OperatingSchedule;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianTaskType;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Generator extends Model
{
    use HasAttachments, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'location_id',
        'name',
        'name_en',
        'manufacturer',
        'model',
        'serial_number',
        'price_per_kw',
        'currency',
        'capacity_kw',
        'lines_count',
        'fuel_type',
        'notes',
        'tank_capacity_liters',
        'rated_voltage',
        'rated_frequency_hz',
        'phase_count',
        'rated_load_kw',
        'service_interval_hours',
        'next_service_due_at',
        'installed_at',
        'status',
        'operating_schedule',
        'operating_start_time',
        'operating_end_time',
    ];

    protected function casts(): array
    {
        return [
            'price_per_kw' => 'decimal:2',
            'currency' => Currency::class,
            'capacity_kw' => 'integer',
            'lines_count' => 'integer',
            'status' => GeneratorStatus::class,
            'operating_schedule' => OperatingSchedule::class,
            'fuel_type' => FuelType::class,
            'last_low_fuel_alert_at' => 'datetime',
            'verified_at' => 'datetime',
            'rated_voltage' => 'integer',
            'rated_frequency_hz' => 'integer',
            'phase_count' => 'integer',
            'rated_load_kw' => 'integer',
            'service_interval_hours' => 'integer',
            'next_service_due_at' => 'date',
            'installed_at' => 'date',
        ];
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function operatingScheduleValues(): array
    {
        return array_column(OperatingSchedule::cases(), 'value');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptionsCount(): int
    {
        return $this->subscriptions()->where('status', SubscriptionStatus::Active)->count();
    }

    public function faults(): HasMany
    {
        return $this->hasMany(Fault::class);
    }

    public function faultPredictions(): HasMany
    {
        return $this->hasMany(FaultPrediction::class);
    }

    public function canServeSchedule(OperatingSchedule $schedule, ?string $startTime = null, ?string $endTime = null): bool
    {
        if ($this->operating_schedule === OperatingSchedule::TwentyFourHours) {
            return true;
        }

        if ($this->operating_schedule !== $schedule) {
            return false;
        }

        if ($schedule === OperatingSchedule::Custom) {
            return $this->operating_start_time === $startTime && $this->operating_end_time === $endTime;
        }

        return true;
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(Technician::class, 'generator_technician')
            ->withTimestamps();
    }

    public function technicianTasks(): HasMany
    {
        return $this->hasMany(TechnicianTask::class);
    }

    public function complaints(): MorphMany
    {
        return $this->morphMany(Complaint::class, 'complainable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'price_per_kw', 'currency', 'capacity_kw', 'owner_id', 'location_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function fuelPurchases(): HasMany
    {
        return $this->hasMany(FuelPurchase::class);
    }

    public function fuelReadings(): HasMany
    {
        return $this->hasMany(FuelReading::class);
    }

    public function latestFuelReading(): HasOne
    {
        return $this->hasOne(FuelReading::class)->latestOfMany('reading_date');
    }

    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(Invoice::class, Subscription::class, 'generator_id', 'subscription_id');
    }

    public function latestMaintenanceTask(): HasOne
    {
        return $this->hasOne(TechnicianTask::class)
            ->whereIn('type', [
                TechnicianTaskType::GeneralMaintenance->value,
                TechnicianTaskType::WiringMaintenance->value,
            ])
            ->latestOfMany();
    }

    public function diagnosticReadings(): HasMany
    {
        return $this->hasMany(GeneratorDiagnosticReading::class)->orderBy('reading_date');
    }

    public function healthReports(): HasMany
    {
        return $this->hasMany(GeneratorHealthReport::class)->latest('period_start');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(GeneratorSchedule::class)->orderBy('starts_at');
    }

    public function isServiceDue(): bool
    {
        return $this->next_service_due_at !== null && $this->next_service_due_at->isPast();
    }
}
