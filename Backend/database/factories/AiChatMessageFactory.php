<?php

namespace Database\Factories;

use App\Enums\AiChatMessageRole;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiChatMessage>
 */
class AiChatMessageFactory extends Factory
{
    protected $model = AiChatMessage::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'session_id' => AiChatSession::factory(),
            'role' => AiChatMessageRole::User->value,
            'content' => fake('ar_SA')->sentence(10),
        ];
    }

    public function assistant(): static
    {
        return $this->state(fn () => [
            'role' => AiChatMessageRole::Assistant->value,
            'content' => fake('ar_SA')->sentence(15),
        ]);
    }
}
