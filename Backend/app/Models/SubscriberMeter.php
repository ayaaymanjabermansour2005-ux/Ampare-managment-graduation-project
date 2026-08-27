<?php

namespace App\Models;

use App\Enums\SubscriberMeterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriberMeter extends Model
{
    use HasFactory, SoftDeletes;

    public static function statusValues(): array
    {
        return array_column(SubscriberMeterStatus::cases(), 'value');
    }

    protected $fillable = [
        'subscriber_id',
        'meter_number',
        'property_label',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriberMeterStatus::class,
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class)->withTrashed();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
