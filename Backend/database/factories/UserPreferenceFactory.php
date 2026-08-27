<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    protected $model = UserPreference::class;
    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => 'locale',
            'value' => 'ar',
        ];
    }
}
