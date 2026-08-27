<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<SubscriptionServiceRequest>
 */
class SubscriptionServiceRequestFactory extends Factory
{
    protected $model = SubscriptionServiceRequest::class;

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'requested_by' => User::factory(),
            'request_type' => 'maintenance',
            'event_type' => null,
            'description' => fake()->sentence(),
            'extra_capacity_kw' => null,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
            'fee_amount' => null,
            'fee_currency' => null,
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
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status' => 'rejected',
            'reviewed_at' => now(),
        ]);
    }
}
