<?php

namespace Database\Factories;

use App\Models\Generator;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'subscriber_meter_id' => SubscriberMeter::factory(),
            'generator_id' => Generator::factory(),
            'agreed_price_per_kw' => fake()->randomFloat(2, 0.3, 1.5),

            'currency' => 'ILS',
            'requested_capacity_kw' => fake()->randomFloat(2, 5, 30),
            'schedule' => fake()->randomElement(['day', 'night', '24h']),
            'contract_type' => fake()->randomElement(['residential', 'commercial']),
            'start_date' => now()->subDays(fake()->numberBetween(1, 60)),
            'status' => 'active',
        ];
    }

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function usd(): static
    {
        return $this->state(fn () => [
            'currency' => 'USD',
            'agreed_price_per_kw' => fake()->randomFloat(2, 0.1, 0.4),
        ]);
    }
}
