<?php

namespace App\Models;

use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelReading extends Model
{
    use HasAttachments, HasFactory, SoftDeletes;

    protected $fillable = [
        'generator_id',
        'recorded_by',
        'tank_level_liters',
        'meter_hours',
        'reading_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'tank_level_liters' => 'decimal:2',
            'meter_hours' => 'decimal:2',
            'reading_date' => 'date',
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
