<?php

namespace App\Models;

use App\Enums\ReviewerRole;
use App\Enums\TechnicianTaskStatus;
use App\Enums\TechnicianTaskType;
use Database\Factories\TechnicianTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TechnicianTask extends Model
{
    /** @use HasFactory<TechnicianTaskFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'generator_id',
        'taskable_type',
        'taskable_id',
        'type',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'type' => TechnicianTaskType::class,
            'status' => TechnicianTaskStatus::class,
            'reviewer_role' => ReviewerRole::class,
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function rating(): HasOne
    {
        return $this->hasOne(TechnicianRating::class);
    }

    public function isPending(): bool
    {
        return $this->status === TechnicianTaskStatus::Pending;
    }

    public function isAssigned(): bool
    {
        return $this->status === TechnicianTaskStatus::Assigned;
    }

    public function isOnTheWay(): bool
    {
        return $this->status === TechnicianTaskStatus::OnTheWay;
    }

    public function isInProgress(): bool
    {
        return $this->status === TechnicianTaskStatus::InProgress;
    }

    public function isWaitingParts(): bool
    {
        return $this->status === TechnicianTaskStatus::WaitingParts;
    }

    public function isSubmitted(): bool
    {
        return $this->status === TechnicianTaskStatus::Submitted;
    }

    public function isApproved(): bool
    {
        return $this->status === TechnicianTaskStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === TechnicianTaskStatus::Rejected;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    public function isAdminOverride(): bool
    {
        return $this->reviewer_role === ReviewerRole::Admin;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'technician_id', 'reviewer_role'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
