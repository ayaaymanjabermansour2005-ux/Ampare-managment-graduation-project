<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\OwnerApplicationStatus;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OwnerApplication extends Model
{
    use HasAttachments, HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'notes',
        'internal_note',
        'generator_name',
        'generator_price_per_kw',
        'generator_currency',
        'generator_capacity_kw',
        'generator_city',
        'generator_neighborhood_id',
        'generator_address',
        'generator_latitude',
        'generator_longitude',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'status' => OwnerApplicationStatus::class,
            'reviewed_at' => 'datetime',
            'generator_currency' => Currency::class,
            'generator_price_per_kw' => 'decimal:2',
            'generator_capacity_kw' => 'integer',
            'generator_latitude' => 'decimal:7',
            'generator_longitude' => 'decimal:7',
        ];
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function generatorNeighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class, 'generator_neighborhood_id');
    }

    public function isPending(): bool
    {
        return $this->status === OwnerApplicationStatus::Pending;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'reviewed_by', 'review_note'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
