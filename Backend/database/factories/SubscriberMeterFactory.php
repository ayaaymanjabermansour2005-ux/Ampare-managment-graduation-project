<?php

namespace Database\Factories;

use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriberMeterFactory extends Factory
{
    protected $model = SubscriberMeter::class;

    public function definition(): array
    {
        return [
            'subscriber_id' => Subscriber::factory(),
            'meter_number' => 'M-'.fake()->unique()->numberBetween(10000, 99999),
            'property_label' => fake()->streetName(),
            'status' => 'active',
        ];
    }
}
