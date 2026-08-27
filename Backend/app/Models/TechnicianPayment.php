<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\TechnicianPaymentStatus;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TechnicianPayment extends Model
{
    use HasAttachments, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'technician_id',
        'owner_id',
        'payment_method_id',
        'amount',
        'currency',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'currency' => Currency::class,
            'status' => TechnicianPaymentStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'reviewed_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
