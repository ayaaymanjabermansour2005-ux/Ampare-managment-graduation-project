<?php

namespace Database\Factories;

use App\Enums\SubscriptionMeterTransferStatus;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\SubscriptionMeterTransferRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionMeterTransferRequestFactory extends Factory
{
    protected $model = SubscriptionMeterTransferRequest::class;

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'from_subscriber_meter_id' => SubscriberMeter::factory(),
            'to_subscriber_meter_id' => SubscriberMeter::factory(),
            'requested_by' => User::factory(),
            'status' => SubscriptionMeterTransferStatus::Pending,
            'reason' => fake()->sentence(),
        ];
    }

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionMeterTransferStatus::Approved,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionMeterTransferStatus::Rejected,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
