<?php

namespace Database\Factories;

use App\Enums\FuelType;
use App\Models\Generator;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Generator>
 */
class GeneratorFactory extends Factory
{
    protected $model = Generator::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'location_id' => Location::factory(),
            'name' => 'مولد '.fake()->streetName(),
            'price_per_kw' => fake()->randomFloat(2, 0.3, 1.5),
            'currency' => 'ILS',
            'capacity_kw' => fake()->numberBetween(20, 150),
            'fuel_type' => fake()->randomElement(FuelType::cases())->value,
            'operating_schedule' => '24h',
            'status' => 'active',
        ];
    }

    public function usd(): static
    {
        return $this->state(fn () => [
            'currency' => 'USD',
            'price_per_kw' => fake()->randomFloat(2, 0.1, 0.4),
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn () => ['status' => 'maintenance']);
    }
}
