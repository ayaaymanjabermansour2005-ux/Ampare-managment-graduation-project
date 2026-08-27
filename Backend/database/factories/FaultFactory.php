<?php

namespace Database\Factories;

use App\Models\Fault;
use App\Models\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Fault>
 */
class FaultFactory extends Factory
{
    protected $model = Fault::class;

    public function definition(): array
    {
        return [
            'generator_id' => Generator::factory(),
            'fault_prediction_id' => null,
            'source' => 'manual',
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => 'medium',
            'reported_at' => now(),
            'status' => 'pending_verification',
        ];
    }

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn() => parent::newModel($attributes));
    }

    public function verified(): static
    {
        return $this->state(fn() => [
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function inRepair(): static
    {
        return $this->verified()->state(fn() => ['status' => 'in_repair']);
    }
}
