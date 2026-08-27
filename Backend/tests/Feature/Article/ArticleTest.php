<?php

namespace Tests\Feature\Article;

use App\Enums\Role as RoleEnum;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        Storage::fake('attachments');
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    public function test_admin_can_create_article(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/articles', [
            'title' => 'خبر جديد',
            'content' => 'تفاصيل الخبر.',
            'title_en' => 'Breaking News',
            'content_en' => 'News details.',
            'is_published' => false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('articles', [
            'title' => 'خبر جديد',
            'title_en' => 'Breaking News',
            'content_en' => 'News details.',
        ]);
    }

    public function test_non_admin_cannot_create_article(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)->postJson('/api/v1/admin/articles', [
            'title' => 'محاولة',
            'content' => 'محتوى.',
        ])->assertStatus(403);
    }

    public function test_admin_can_upload_image_to_article(): void
    {
        $admin = $this->makeAdmin();
        $article = Article::create([
            'title' => 'مقال',
            'slug' => Article::generateUniqueSlug('مقال'),
            'content' => 'محتوى.',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/articles/{$article->id}/attachments", [
                'file' => UploadedFile::fake()->image('gallery1.jpg'),
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Article::class,
            'attachable_id' => $article->id,
            'document_type' => 'article_image',
        ]);
    }

    public function test_non_admin_cannot_upload_image_to_article(): void
    {
        $admin = $this->makeAdmin();
        $article = Article::create([
            'title' => 'مقال',
            'slug' => Article::generateUniqueSlug('مقال'),
            'content' => 'محتوى.',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->postJson("/api/v1/admin/articles/{$article->id}/attachments", [
                'file' => UploadedFile::fake()->image('gallery1.jpg'),
            ])
            ->assertStatus(403);
    }

    public function test_published_article_is_visible_to_guests_with_images(): void
    {
        $admin = $this->makeAdmin();
        $article = Article::create([
            'title' => 'خبر منشور',
            'slug' => Article::generateUniqueSlug('خبر منشور'),
            'content' => 'محتوى.',
            'author_id' => $admin->id,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $article->attachments()->create([
            'uploaded_by' => $admin->id,
            'document_type' => 'article_image',
            'original_name' => 'a.jpg',
            'stored_name' => 'a.jpg',
            'disk' => 'attachments',
            'path' => 'attachments/article/a.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 100,
        ]);

        $response = $this->getJson("/api/articles/{$article->slug}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data.images'));
    }

    public function test_unpublished_article_is_not_visible_publicly(): void
    {
        $admin = $this->makeAdmin();
        $article = Article::create([
            'title' => 'مسودة',
            'slug' => Article::generateUniqueSlug('مسودة'),
            'content' => 'محتوى.',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);

        $this->getJson("/api/articles/{$article->slug}")->assertStatus(404);
    }
}
