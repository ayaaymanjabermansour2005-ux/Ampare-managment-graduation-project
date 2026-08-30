<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentSource;
use App\Enums\PaymentStatus;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use HasAttachments, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'payment_method_id',
        'source',
        'amount',
        'currency',
        'exchange_rate',
        'amount_ils',
        'transaction_reference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'currency' => Currency::class,
            'exchange_rate' => 'decimal:4',
            'amount_ils' => 'decimal:2',
            'paid_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'source' => PaymentSource::class,
            'status' => PaymentStatus::class,
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PaymentReview::class)->latest();
    }

    public function complaints(): MorphMany
    {
        return $this->morphMany(Complaint::class, 'complainable');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', PaymentStatus::Paid);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'payment_method_id', 'processed_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
