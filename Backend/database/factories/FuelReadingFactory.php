<?php

namespace Database\Factories;

use App\Models\FuelReading;
use App\Models\Generator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelReading>
 */
class FuelReadingFactory extends Factory
{
    protected $model = FuelReading::class;

    public function definition(): array
    {
        return [
            'generator_id' => Generator::factory(),
            'recorded_by' => User::factory(),
            'tank_level_liters' => fake()->randomFloat(2, 0, 1000),
            'meter_hours' => fake()->randomFloat(2, 0, 5000),
            'reading_date' => now()->subDays(fake()->numberBetween(1, 60))->toDateString(),
            'notes' => null,
        ];
    }
}
