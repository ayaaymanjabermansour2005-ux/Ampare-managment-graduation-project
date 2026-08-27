<?php

namespace Database\Factories;

use App\Models\Technician;
use App\Models\TechnicianRating;
use App\Models\TechnicianTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicianRating>
 */
class TechnicianRatingFactory extends Factory
{
    protected $model = TechnicianRating::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'technician_task_id' => TechnicianTask::factory(),
            'technician_id' => Technician::factory(),
            'rated_by' => User::factory(),
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake('ar_SA')->optional()->sentence(8),
        ];
    }
}
