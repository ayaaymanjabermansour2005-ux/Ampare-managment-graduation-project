<?php

namespace Database\Factories;

use App\Enums\OwnerApplicationStatus;
use App\Models\OwnerApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<OwnerApplication>
 */
class OwnerApplicationFactory extends Factory
{
    protected $model = OwnerApplication::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'name' => fake('ar_SA')->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '059'.fake()->numerify('#######'),
            'password' => Hash::make('password'),
            'notes' => fake('ar_SA')->sentence(10),
            'generator_name' => 'مولد '.fake('ar_SA')->streetName(),
            'generator_price_per_kw' => fake()->randomFloat(2, 0.3, 1.5),
            'generator_currency' => 'ILS',
            'generator_capacity_kw' => fake()->numberBetween(20, 120),
            'generator_city' => 'غزة',
            'generator_address' => fake('ar_SA')->streetAddress(),
            'status' => OwnerApplicationStatus::Pending->value,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => OwnerApplicationStatus::Approved->value,
            'reviewed_at' => now()->subDays(3),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => OwnerApplicationStatus::Rejected->value,
            'reviewed_at' => now()->subDays(3),
            'internal_note' => fake('ar_SA')->sentence(8),
        ]);
    }
}
