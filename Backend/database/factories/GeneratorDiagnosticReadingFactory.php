<?php

namespace Database\Factories;

use App\Enums\SmokeLevel;
use App\Enums\VibrationLevel;
use App\Models\Generator;
use App\Models\GeneratorDiagnosticReading;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratorDiagnosticReading>
 */
class GeneratorDiagnosticReadingFactory extends Factory
{
    protected $model = GeneratorDiagnosticReading::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'generator_id' => Generator::factory(),
            'recorded_by' => User::factory(),
            'operating_hours' => fake()->randomFloat(2, 100, 5000),
            'temperature_celsius' => fake()->randomFloat(2, 60, 95),
            'oil_level_percent' => fake()->randomFloat(2, 40, 100),
            'load_percent' => fake()->randomFloat(2, 20, 100),
            'voltage' => fake()->randomFloat(2, 210, 230),
            'frequency_hz' => fake()->randomFloat(2, 49, 51),
            'smoke_level' => SmokeLevel::None->value,
            'vibration_level' => VibrationLevel::Normal->value,
            'notes' => fake('ar_SA')->optional()->sentence(6),
            'reading_date' => now()->subDays(fake()->numberBetween(0, 20))->toDateString(),
        ];
    }

    public function warningSigns(): static
    {
        return $this->state(fn () => [
            'smoke_level' => SmokeLevel::Heavy->value,
            'vibration_level' => VibrationLevel::Abnormal->value,
            'temperature_celsius' => fake()->randomFloat(2, 96, 115),
        ]);
    }
}
