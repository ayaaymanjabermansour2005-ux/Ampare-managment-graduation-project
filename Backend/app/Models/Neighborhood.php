<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Neighborhood extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
    ];

    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function generators(): HasManyThrough
    {
        return $this->hasManyThrough(
            Generator::class,
            Location::class,
            'neighborhood_id',
            'location_id'
        );
    }
}
