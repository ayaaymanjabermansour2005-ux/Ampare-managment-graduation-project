<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'neighborhood_id' => Neighborhood::factory(),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(31.2, 31.6),
            'longitude' => fake()->longitude(34.2, 34.6),
        ];
    }

    public function withoutCoordinates(): static
    {
        return $this->state(fn () => [
            'latitude' => null,
            'longitude' => null,
        ]);
    }
}
