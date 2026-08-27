<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionOverride;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionOverride>
 */
class SubscriptionOverrideFactory extends Factory
{
    protected $model = SubscriptionOverride::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'extra_capacity_kw' => fake()->randomFloat(2, 2, 15),
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->addHours(4),
        ];
    }
}
