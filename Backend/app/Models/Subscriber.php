<?php

namespace App\Models;

use App\Enums\BeneficiaryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscriber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'neighborhood_id',
        'address',
        'joined_at',
        'beneficiary_type',
        'beneficiary_type_changed_by',
        'beneficiary_type_changed_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'beneficiary_type' => BeneficiaryType::class,
            'beneficiary_type_changed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function beneficiaryTypeChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_type_changed_by');
    }

    public function meters(): HasMany
    {
        return $this->hasMany(SubscriberMeter::class);
    }

    public function subscriptions(): HasManyThrough
    {
        return $this->hasManyThrough(Subscription::class, SubscriberMeter::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'submitted_by', 'user_id');
    }

    public function receivedSpecialOffers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'offer_subscriber')
            ->withTimestamps();
    }
}
