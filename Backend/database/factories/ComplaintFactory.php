<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'submitted_by' => User::factory(),
            'subject' => fake('ar_SA')->sentence(4),
            'description' => fake('ar_SA')->sentence(15),
            'status' => ComplaintStatus::Pending->value,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => ComplaintStatus::InProgress->value]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplaintStatus::Resolved->value,
            'resolved_by' => User::factory(),
            'resolved_at' => now()->subDays(fake()->numberBetween(1, 10)),
            'resolution_note' => fake('ar_SA')->sentence(10),
        ]);
    }
}
