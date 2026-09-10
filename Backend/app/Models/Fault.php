<?php

namespace App\Models;

use App\Enums\FaultPriority;
use App\Enums\FaultRepairMethod;
use App\Enums\FaultSource;
use App\Enums\FaultStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Fault extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'generator_id',
        'fault_prediction_id',
        'ai_chat_session_id',
        'conversation_id',
        'reported_by',
        'source',
        'title',
        'description',
        'priority',
        'reported_at',
        'status',
        'verified_by',
        'verified_at',
        'repair_method',
        'resolved_at',
        'closed_by',
        'closed_at',
        'admin_override_reason',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'verified_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'source' => FaultSource::class,
            'priority' => FaultPriority::class,
            'status' => FaultStatus::class,
            'repair_method' => FaultRepairMethod::class,
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }

    public function prediction(): BelongsTo
    {
        return $this->belongsTo(FaultPrediction::class, 'fault_prediction_id');
    }

    public function aiChatSession(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_chat_session_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function technicianTasks(): MorphMany
    {
        return $this->morphMany(TechnicianTask::class, 'taskable');
    }

    public function complaints(): MorphMany
    {
        return $this->morphMany(Complaint::class, 'complainable');
    }

    public function isPendingVerification(): bool
    {
        return $this->status === FaultStatus::PendingVerification;
    }

    public function isVerified(): bool
    {
        return $this->status === FaultStatus::Verified;
    }

    public function isInRepair(): bool
    {
        return $this->status === FaultStatus::InRepair;
    }

    public function isResolved(): bool
    {
        return $this->status === FaultStatus::Resolved;
    }

    public function isClosed(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * Scope to faults still considered "open" (see FaultStatus::openValues()).
     * Table-qualified because callers often join generators/locations, which also have a `status` column.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn($this->getTable().'.status', FaultStatus::openValues());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'repair_method', 'admin_override_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
