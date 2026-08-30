<?php

namespace Database\Factories;

use App\Models\Generator;
use App\Models\GeneratorSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<GeneratorSchedule>
 */
class GeneratorScheduleFactory extends Factory
{
    protected $model = GeneratorSchedule::class;

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'generator_id' => Generator::factory(),
            'starts_at' => now()->addDay()->setTime(18, 0),
            'ends_at' => now()->addDay()->setTime(23, 0),
            'note' => fake('ar_SA')->sentence(6),
            'created_by' => User::factory(),
        ];
    }
}
