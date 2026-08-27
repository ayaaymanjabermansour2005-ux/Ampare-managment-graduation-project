<?php

namespace Database\Factories;

use App\Enums\PaymentReviewStatus;
use App\Models\Payment;
use App\Models\PaymentReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentReview>
 */
class PaymentReviewFactory extends Factory
{
    protected $model = PaymentReview::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'reviewed_by' => User::factory(),
            'status' => PaymentReviewStatus::Approved->value,
            'reason' => null,
        ];
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => PaymentReviewStatus::Rejected->value,
            'reason' => fake('ar_SA')->sentence(8),
        ]);
    }
}
