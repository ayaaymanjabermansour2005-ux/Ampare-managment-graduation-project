<?php

namespace Database\Factories;

use App\Enums\AiChatContextType;
use App\Models\AiChatSession;
use App\Models\Generator;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiChatSession>
 */
class AiChatSessionFactory extends Factory
{
    protected $model = AiChatSession::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'generator_id' => Generator::factory(),
            'title' => fake('ar_SA')->sentence(4),
            'context_type' => AiChatContextType::OwnerDiagnostic->value,
        ];
    }

    public function subscriberSupport(): static
    {
        return $this->state(fn () => [
            'context_type' => AiChatContextType::SubscriberSupport->value,
            'generator_id' => null,
        ]);
    }
}
