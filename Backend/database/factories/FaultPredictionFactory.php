<?php

namespace Database\Factories;

use App\Enums\FaultPredictionSource;
use App\Enums\FaultPredictionStatus;
use App\Models\FaultPrediction;
use App\Models\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<FaultPrediction>
 */
class FaultPredictionFactory extends Factory
{
    protected $model = FaultPrediction::class;

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'generator_id' => Generator::factory(),
            'source' => FaultPredictionSource::SensorAnalysis->value,
            'is_actual_fault' => null,
            'prediction_type' => 'overheating_risk',
            'confidence' => fake()->randomFloat(2, 0.5, 0.95),
            'recommendation' => fake('ar_SA')->sentence(10),
            'input_snapshot' => ['temperature_celsius' => fake()->randomFloat(2, 90, 110)],
            'status' => FaultPredictionStatus::Pending->value,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => FaultPredictionStatus::Confirmed->value,
            'is_actual_fault' => true,
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn () => [
            'status' => FaultPredictionStatus::Dismissed->value,
            'is_actual_fault' => false,
        ]);
    }
}
