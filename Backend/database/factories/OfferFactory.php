<?php

namespace Database\Factories;

use App\Enums\OfferDiscountType;
use App\Enums\OfferStatus;
use App\Enums\OfferTargetMode;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'title' => fake('ar_SA')->sentence(3),
            'description' => fake('ar_SA')->sentence(12),
            'discount_type' => OfferDiscountType::Percentage->value,
            'discount_value' => fake()->numberBetween(5, 25),
            'target_mode' => OfferTargetMode::All->value,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
            'status' => OfferStatus::Active->value,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->subDays(60)->toDateString(),
            'end_date' => now()->subDays(30)->toDateString(),
            'status' => OfferStatus::Cancelled->value,
        ]);
    }
}
