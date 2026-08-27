<?php

namespace Database\Factories;

use App\Enums\MeterReadingStatus;
use App\Models\MeterReading;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReading>
 */
class MeterReadingFactory extends Factory
{
    protected $model = MeterReading::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        $previous = fake()->numberBetween(500, 2000);

        return [
            'subscription_id' => Subscription::factory(),
            'reading_date' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
            'previous_reading' => $previous,
            'current_reading' => $previous + fake()->numberBetween(10, 60),
            'created_by' => User::factory(),
            'status' => MeterReadingStatus::Approved->value,
        ];
    }

    public function pendingApproval(): static
    {
        return $this->state(fn () => ['status' => MeterReadingStatus::PendingApproval->value]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => MeterReadingStatus::Rejected->value,
            'rejection_reason' => fake('ar_SA')->sentence(8),
        ]);
    }
}
