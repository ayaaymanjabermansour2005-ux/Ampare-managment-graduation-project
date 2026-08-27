<?php

namespace Database\Factories;

use App\Models\Neighborhood;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriberFactory extends Factory
{
    protected $model = Subscriber::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'neighborhood_id' => Neighborhood::factory(),
            'address' => fake()->streetAddress(),
            'joined_at' => now(),
        ];
    }
}
