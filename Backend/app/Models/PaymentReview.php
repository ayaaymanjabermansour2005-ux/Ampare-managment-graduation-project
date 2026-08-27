<?php

namespace App\Models;

use App\Enums\PaymentReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'reviewed_by',
        'status',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentReviewStatus::class,
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
