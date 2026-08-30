<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 20, 500);

        return [
            'subscription_id' => Subscription::factory(),
            'meter_reading_id' => null,
            'amount' => $amount,
            'discount_amount' => 0,
            'discount_id' => null,
            'final_amount' => $amount,
            'currency' => 'ILS',
            'exchange_rate' => null,
            'final_amount_ils' => $amount,
            'due_date' => now()->addDays(7),
            'status' => 'pending',
        ];
    }

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function usd(float $rate = 3.70): static
    {
        return $this->state(function (array $attrs) use ($rate) {
            $amount = $attrs['amount'] ?? fake()->randomFloat(2, 20, 200);

            return [
                'currency' => 'USD',
                'exchange_rate' => $rate,
                'final_amount_ils' => round((float) $amount * $rate, 2),
            ];
        });
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid']);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => 'overdue',
            'due_date' => now()->subDays(5),
        ]);
    }
}
