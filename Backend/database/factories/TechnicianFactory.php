<?php

namespace Database\Factories;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technician>
 */
class TechnicianFactory extends Factory
{
    protected $model = Technician::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'owner_id' => User::factory(),
            'status' => 'active',
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['status' => 'inactive']);
    }
}
