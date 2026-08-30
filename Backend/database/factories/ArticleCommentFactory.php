<?php

namespace Database\Factories;

use App\Enums\ArticleCommentStatus;
use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ArticleComment>
 */
class ArticleCommentFactory extends Factory
{
    protected $model = ArticleComment::class;

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'name' => fake('ar_SA')->name(),
            'email' => fake()->safeEmail(),
            'comment' => fake('ar_SA')->sentence(12),
            'status' => ArticleCommentStatus::Pending->value,
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => ArticleCommentStatus::Approved->value]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => ArticleCommentStatus::Rejected->value]);
    }
}
