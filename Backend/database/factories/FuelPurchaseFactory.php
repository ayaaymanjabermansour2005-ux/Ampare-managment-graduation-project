<?php

namespace Database\Factories;

use App\Models\FuelPurchase;
use App\Models\Generator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelPurchase>
 */
class FuelPurchaseFactory extends Factory
{
    protected $model = FuelPurchase::class;

    public function definition(): array
    {
        $liters = fake()->randomFloat(2, 20, 500);
        $costAmount = fake()->randomFloat(2, 50, 3000);

        return [
            'generator_id' => Generator::factory(),
            'recorded_by' => User::factory(),
            'liters' => $liters,
            'cost_amount' => $costAmount,
            'currency' => 'ILS',
            'exchange_rate' => null,
            'cost_amount_ils' => $costAmount,
            'purchased_at' => now()->subDays(fake()->numberBetween(1, 60))->toDateString(),
            'notes' => null,
        ];
    }

    public function usd(float $exchangeRate = 3.70): static
    {
        return $this->state(function (array $attributes) use ($exchangeRate) {
            $costAmount = $attributes['cost_amount'] ?? fake()->randomFloat(2, 50, 3000);

            return [
                'currency' => 'USD',
                'exchange_rate' => $exchangeRate,
                'cost_amount' => $costAmount,
                'cost_amount_ils' => round($costAmount * $exchangeRate, 2),
            ];
        });
    }
}
