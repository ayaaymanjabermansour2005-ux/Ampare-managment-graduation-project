<?php

namespace App\Models;

use App\Enums\TechnicianStatus;
use App\Traits\HasAttachments;
use Database\Factories\TechnicianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Technician extends Model
{
    /** @use HasFactory<TechnicianFactory> */
    use HasAttachments, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'owner_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => TechnicianStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function generators(): BelongsToMany
    {
        return $this->belongsToMany(Generator::class, 'generator_technician')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TechnicianTask::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TechnicianPayment::class);
    }

    public function isActive(): bool
    {
        return $this->status === TechnicianStatus::Active;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'owner_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(TechnicianRating::class);
    }

    public function averageRating(): ?float
    {
        $avg = $this->ratings()->avg('rating');

        return $avg ? round($avg, 1) : null;
    }
}
