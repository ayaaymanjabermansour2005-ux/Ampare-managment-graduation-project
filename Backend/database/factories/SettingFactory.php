<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
        ];
    }
}
