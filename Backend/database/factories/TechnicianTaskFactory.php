<?php

namespace Database\Factories;

use App\Models\Generator;
use App\Models\Technician;
use App\Models\TechnicianTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<TechnicianTask>
 */
class TechnicianTaskFactory extends Factory
{
    protected $model = TechnicianTask::class;

    public function definition(): array
    {
        return [
            'generator_id' => Generator::factory(),
            'technician_id' => null,
            'requested_by' => User::factory(),
            'assigned_by' => null,
            'taskable_type' => null,
            'taskable_id' => null,
            'type' => 'general_maintenance',
            'status' => 'pending',
            'instructions' => fake()->sentence(),
        ];
    }

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'technician_id' => Technician::factory(),
            'assigned_by' => User::factory(),
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->assigned()->state(fn () => [
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function faultRepair(): static
    {
        return $this->state(fn () => ['type' => 'fault_repair']);
    }
}
