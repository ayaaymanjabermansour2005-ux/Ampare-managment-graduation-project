<?php

namespace App\Models;

use App\Enums\BeneficiaryType;
use App\Enums\OfferDiscountType;
use App\Enums\OfferStatus;
use App\Enums\OfferTargetMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'offers';

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'target_mode',
        'beneficiary_type',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'discount_type' => OfferDiscountType::class,
            'target_mode' => OfferTargetMode::class,
            'beneficiary_type' => BeneficiaryType::class,
            'status' => OfferStatus::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function targetedSubscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscriber::class, 'offer_subscriber')
            ->withTimestamps();
    }
}
