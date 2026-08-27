<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10, 300);

        return [
            'invoice_id' => Invoice::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'source' => 'subscriber',
            'amount' => $amount,
            'currency' => 'ILS',
            'exchange_rate' => null,
            'amount_ils' => $amount,
            'transaction_reference' => fake()->uuid(),
            'note' => null,
            'status' => 'pending',
            'paid_at' => null,
            'processed_by' => null,
        ];
    }

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn() => parent::newModel($attributes));
    }

    public function usd(float $rate = 3.70): static
    {
        return $this->state(function (array $attrs) use ($rate) {
            $amount = $attrs['amount'] ?? fake()->randomFloat(2, 10, 100);

            return [
                'currency' => 'USD',
                'exchange_rate' => $rate,
                'amount_ils' => round((float) $amount * $rate, 2),
            ];
        });
    }

    public function paid(): static
    {
        return $this->state(fn() => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function needsCorrection(): static
    {
        return $this->state(fn() => [
            'status' => 'needs_correction',
            'review_note' => 'الإثبات غير واضح، يرجى إعادة الرفع.',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status' => 'rejected',
            'rejection_reason' => 'المبلغ لا يطابق الفاتورة.',
        ]);
    }
}
