<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleRating;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ArticleRating>
 */
class ArticleRatingFactory extends Factory
{
    protected $model = ArticleRating::class;

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'visitor_hash' => fake()->sha256(),
        ];
    }
}
