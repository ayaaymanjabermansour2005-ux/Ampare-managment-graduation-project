<?php

namespace Database\Factories;

use App\Models\Technician;
use App\Models\TechnicianPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<TechnicianPayment>
 */
class TechnicianPaymentFactory extends Factory
{
    protected $model = TechnicianPayment::class;

    public function definition(): array
    {
        return [
            'technician_id' => Technician::factory(),
            'owner_id' => User::factory(),
            'payment_method_id' => null,
            'amount' => fake()->randomFloat(2, 50, 500),
            'currency' => 'ILS',
            'note' => fake()->optional()->sentence(),
            'status' => 'pending',
            'created_by' => User::factory(),
        ];
    }

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn() => parent::newModel($attributes));
    }

    public function approved(): static
    {
        return $this->state(fn() => [
            'status' => 'approved',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status' => 'rejected',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
