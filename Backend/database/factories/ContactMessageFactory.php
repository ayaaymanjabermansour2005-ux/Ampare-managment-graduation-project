<?php

namespace Database\Factories;

use App\Enums\ContactMessageStatus;
use App\Enums\ContactMessageSubject;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'name' => fake('ar_SA')->name(),
            'phone' => '059'.fake()->numerify('#######'),
            'email' => fake()->safeEmail(),
            'subject' => fake()->randomElement(ContactMessageSubject::cases())->value,
            'message' => fake('ar_SA')->sentence(20),
            'status' => ContactMessageStatus::New->value,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => ContactMessageStatus::InProgress->value]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => ContactMessageStatus::Resolved->value,
            'admin_note' => fake('ar_SA')->sentence(8),
            'handled_at' => now()->subDays(fake()->numberBetween(1, 5)),
        ]);
    }
}
