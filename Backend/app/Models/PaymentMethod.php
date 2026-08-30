<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentMethod extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'is_default',
        'currency',
        'bank_name',
        'account_name',
        'account_number',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'type' => PaymentMethodType::class,
            'currency' => Currency::class,
            'account_number' => 'encrypted',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'is_default', 'currency', 'bank_name', 'account_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $properties = $activity->properties;

        foreach (['attributes', 'old'] as $bucket) {
            if ($properties->has("{$bucket}.account_number")) {
                $properties = $properties->put(
                    "{$bucket}.account_number",
                    self::maskAccountNumber($properties->get("{$bucket}.account_number"))
                );
            }
        }

        $activity->properties = $properties;
    }

    public static function maskAccountNumber(?string $value): ?string
    {
        if (blank($value)) {
            return $value;
        }

        $length = mb_strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).mb_substr($value, -4);
    }
}
