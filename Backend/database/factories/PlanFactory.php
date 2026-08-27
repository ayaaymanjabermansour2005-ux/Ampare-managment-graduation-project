<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Pro', 'Enterprise']),
            'code' => fake()->unique()->slug(2),
            'max_generators' => fake()->numberBetween(1, 20),
            'price_monthly' => fake()->randomFloat(2, 0, 100),
            'currency' => 'ILS',
            'is_active' => true,
        ];
    }
}
