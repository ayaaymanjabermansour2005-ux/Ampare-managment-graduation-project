<?php

namespace Database\Factories;

use App\Models\OwnerRating;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OwnerRating>
 */
class OwnerRatingFactory extends Factory
{
    protected $model = OwnerRating::class;

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'owner_id' => User::factory(),
            'rated_by' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
        ];
    }

    public function lowRating(): static
    {
        return $this->state(fn() => ['rating' => fake()->numberBetween(1, 2)]);
    }
}
