<?php

namespace Database\Factories;

use App\Models\CommissionTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<CommissionTier>
 */
class CommissionTierFactory extends Factory
{
    protected $model = CommissionTier::class;

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'min_generators_count' => 1,
            'max_generators_count' => 3,
            'commission_rate' => fake()->randomFloat(2, 2, 10),
            'is_active' => true,
        ];
    }
}
