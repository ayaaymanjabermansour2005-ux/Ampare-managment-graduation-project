<?php

namespace App\Models;

use App\Enums\MeterReadingStatus;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string|null $reading_warning Transient, non-persisted warning attached in-memory by
 *                                        MeterReadingService::create() when a reading is submitted before the expected billing date; surfaced
 *                                        to the API response by MeterReadingResource. Never stored in the database.
 */
class MeterReading extends Model
{
    use HasAttachments, HasFactory;

    protected $fillable = [
        'subscription_id',
        'reading_date',
        'previous_reading',
        'current_reading',
        'created_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'reading_date' => 'date',
            'previous_reading' => 'decimal:2',
            'current_reading' => 'decimal:2',
            'consumed_kw' => 'decimal:2',
            'status' => MeterReadingStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
