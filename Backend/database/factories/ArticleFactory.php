<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function newModel(array $attributes = [])
    {
        return Model::unguarded(fn () => parent::newModel($attributes));
    }

    public function definition(): array
    {
        $title = fake('ar_SA')->sentence(6);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'excerpt' => fake('ar_SA')->sentence(15),
            'content' => fake('ar_SA')->paragraphs(5, true),
            'title_en' => fake()->sentence(6),
            'excerpt_en' => fake()->sentence(15),
            'content_en' => fake()->paragraphs(5, true),
            'cover_image_url' => null,
            'author_id' => User::factory(),
            'is_published' => true,
            'published_at' => now()->subDays(fake()->numberBetween(0, 60)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
