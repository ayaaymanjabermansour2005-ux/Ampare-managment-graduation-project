<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\PlatformCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<PlatformCommission>
 */
class PlatformCommissionFactory extends Factory
{
    protected $model = PlatformCommission::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'owner_id' => User::factory(),
            'commission_rate' => 10,
            'commission_amount' => fake()->randomFloat(2, 5, 200),
            'status' => 'pending',
            'earned_at' => null,
            'paid_at' => null,
        ];
    }

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function earned(): static
    {
        return $this->state(fn () => [
            'status' => 'earned',
            'earned_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'paid',
            'earned_at' => now()->subDay(),
            'paid_at' => now(),
        ]);
    }
}
