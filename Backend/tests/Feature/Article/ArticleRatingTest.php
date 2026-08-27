<?php

namespace Tests\Feature\Article;

use App\Enums\Role as RoleEnum;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ArticleRatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeArticle(): Article
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return Article::create([
            'title' => 'مقال للتقييم',
            'slug' => Article::generateUniqueSlug('مقال للتقييم'),
            'content' => 'محتوى.',
            'author_id' => $admin->id,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function test_guest_can_rate_article(): void
    {
        $article = $this->makeArticle();

        $response = $this->postJson("/api/articles/{$article->slug}/rating", ['rating' => 4]);

        $response->assertOk();
        $this->assertSame(4, $response->json('data.my_rating'));
        $this->assertSame(1, $response->json('data.count'));
        $this->assertDatabaseHas('article_ratings', [
            'article_id' => $article->id,
            'rating' => 4,
        ]);
    }

    public function test_rating_out_of_range_is_rejected(): void
    {
        $article = $this->makeArticle();

        $this->postJson("/api/articles/{$article->slug}/rating", ['rating' => 6])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);

        $this->postJson("/api/articles/{$article->slug}/rating", ['rating' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_same_visitor_updates_existing_rating_instead_of_duplicating(): void
    {
        $article = $this->makeArticle();

        $this->postJson("/api/articles/{$article->slug}/rating", ['rating' => 3])->assertOk();
        $response = $this->postJson("/api/articles/{$article->slug}/rating", ['rating' => 5]);

        $response->assertOk();
        $this->assertSame(5, $response->json('data.my_rating'));
        $this->assertSame(1, $response->json('data.count'));
        $this->assertDatabaseCount('article_ratings', 1);
        $this->assertDatabaseHas('article_ratings', [
            'article_id' => $article->id,
            'rating' => 5,
        ]);
    }

    public function test_rating_submission_is_rate_limited(): void
    {
        Cache::flush();

        $article = $this->makeArticle();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/articles/{$article->slug}/rating", ['rating' => 3])->assertOk();
        }

        $this->postJson("/api/articles/{$article->slug}/rating", ['rating' => 3])->assertStatus(429);
    }

    public function test_show_returns_average_and_count(): void
    {
        $article = $this->makeArticle();
        $article->ratings()->create(['rating' => 4, 'visitor_hash' => hash('sha256', 'a')]);
        $article->ratings()->create(['rating' => 2, 'visitor_hash' => hash('sha256', 'b')]);

        $response = $this->getJson("/api/articles/{$article->slug}/rating");

        $response->assertOk();
        $this->assertEquals(3.0, $response->json('data.average'));
        $this->assertSame(2, $response->json('data.count'));
        $this->assertNull($response->json('data.my_rating'));
    }
}
