<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'max_generators', 'price_monthly', 'currency', 'is_active'];

    protected function casts(): array
    {
        return [
            'max_generators' => 'integer',
            'price_monthly' => 'decimal:2',
            'currency' => Currency::class,
            'is_active' => 'boolean',
        ];
    }

    public function owners(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
