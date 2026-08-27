<?php

namespace App\Models;

use App\Enums\Currency;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelPurchase extends Model
{
    use HasAttachments, HasFactory, SoftDeletes;

    protected $fillable = [
        'generator_id',
        'recorded_by',
        'liters',
        'cost_amount',
        'currency',
        'exchange_rate',
        'cost_amount_ils',
        'purchased_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'liters' => 'decimal:2',
            'cost_amount' => 'decimal:2',
            'currency' => Currency::class,
            'exchange_rate' => 'decimal:4',
            'cost_amount_ils' => 'decimal:2',
            'purchased_at' => 'date',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
