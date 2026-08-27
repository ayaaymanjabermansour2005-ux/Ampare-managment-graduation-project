<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'wallet',
            'is_default' => true,
            'currency' => 'ILS',
            'bank_name' => null,
            'account_name' => fake()->name(),
            'account_number' => fake()->numerify('#########'),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PaymentMethod $method) {
            if ($this->resolvesToCash($method)) {
                $method->currency = null;
            }
        });
    }

    private function resolvesToCash(PaymentMethod $method): bool
    {
        $type = $method->type;

        return $type === PaymentMethodType::Cash || $type === PaymentMethodType::Cash->value;
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'type' => 'bank',
            'bank_name' => fake()->randomElement(['بنك فلسطين', 'بنك القدس']),
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'type' => 'cash',
            'currency' => null,
            'bank_name' => null,
            'account_name' => null,
            'account_number' => null,
        ]);
    }

    public function usd(): static
    {
        return $this->state(fn () => ['currency' => 'USD']);
    }
}
