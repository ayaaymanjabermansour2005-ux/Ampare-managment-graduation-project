<?php

namespace Database\Factories;

use App\Models\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;

class NeighborhoodFactory extends Factory
{
    protected $model = Neighborhood::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->citySuffix() . ' ' . fake()->unique()->numberBetween(1000, 999999),
        ];
    }
}
