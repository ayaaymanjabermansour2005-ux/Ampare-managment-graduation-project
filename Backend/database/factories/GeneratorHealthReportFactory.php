<?php

namespace Database\Factories;

use App\Enums\HealthRiskLevel;
use App\Models\Generator;
use App\Models\GeneratorHealthReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<GeneratorHealthReport>
 */
class GeneratorHealthReportFactory extends Factory
{
    protected $model = GeneratorHealthReport::class;

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'generator_id' => Generator::factory(),
            'period_start' => now()->subDays(30)->toDateString(),
            'period_end' => now()->toDateString(),
            'risk_level' => HealthRiskLevel::Low->value,
            'summary' => fake('ar_SA')->sentence(15),
            'recommendation' => fake('ar_SA')->sentence(10),
            'input_snapshot' => ['readings_count' => fake()->numberBetween(3, 10)],
        ];
    }

    public function highRisk(): static
    {
        return $this->state(fn () => ['risk_level' => HealthRiskLevel::High->value]);
    }
}
