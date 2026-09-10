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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArticleCommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    private function makeArticle(User $admin): Article
    {
        return Article::create([
            'title' => 'مقال تجريبي',
            'slug' => Article::generateUniqueSlug('مقال تجريبي'),
            'content' => 'محتوى المقال.',
            'author_id' => $admin->id,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function test_guest_can_submit_article_comment(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);

        $response = $this->postJson("/api/articles/{$article->slug}/comments", [
            'name' => 'زائر',
            'email' => 'visitor@example.com',
            'comment' => 'تعليق رائع على المقال.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('article_comments', [
            'article_id' => $article->id,
            'status' => 'pending',
        ]);
    }

    public function test_article_comment_honeypot_blocks_bots(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);

        $response = $this->postJson("/api/articles/{$article->slug}/comments", [
            'name' => 'بوت',
            'comment' => 'رسالة تلقائية.',
            'website' => 'https://spam.example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['website']);
        $this->assertDatabaseCount('article_comments', 0);
    }

    public function test_article_comment_submission_is_rate_limited(): void
    {
        Cache::flush();

        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/articles/{$article->slug}/comments", [
                'name' => "زائر {$i}",
                'comment' => "تعليق رقم {$i}.",
            ])->assertStatus(201);
        }

        $this->postJson("/api/articles/{$article->slug}/comments", [
            'name' => 'زائر إضافي',
            'comment' => 'تعليق زائد عن الحد.',
        ])->assertStatus(429);
    }

    public function test_pending_comment_is_not_visible_in_public_index(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);
        $article->comments()->create([
            'name' => 'زائر',
            'comment' => 'بانتظار المراجعة.',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/articles/{$article->slug}/comments");

        $response->assertOk();
        $this->assertCount(0, $response->json('data.data'));
    }

    public function test_approved_comment_is_visible_without_sensitive_fields(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);
        $article->comments()->create([
            'name' => 'زائر',
            'email' => 'secret@example.com',
            'comment' => 'تعليق معتمد.',
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/articles/{$article->slug}/comments");

        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertArrayNotHasKey('email', $data[0]);
        $this->assertArrayNotHasKey('status', $data[0]);
    }

    public function test_guest_cannot_access_admin_comment_moderation(): void
    {
        $this->getJson('/api/v1/admin/article-comments')->assertStatus(401);
    }

    public function test_non_admin_cannot_moderate_comments(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/admin/article-comments')
            ->assertStatus(403);
    }

    /**
     * تدقيق شامل — الجولة السابعة: قبل هالإصلاح، مراجعة التعليقات كانت
     * محمية بـ isAdmin() فقط. صار فيه صلاحية article-comments.moderate
     * دقيقة — هاد الاختبار يتحقق أن أدمن بدونها (رغم دوره admin) يُرفض.
     */
    public function test_admin_without_moderate_permission_cannot_approve_comment(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);
        $comment = $article->comments()->create([
            'name' => 'زائر',
            'comment' => 'تعليق.',
            'status' => 'pending',
        ]);
        Role::findByName(RoleEnum::ADMIN->value, 'sanctum')->revokePermissionTo('article-comments.moderate');

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/article-comments/{$comment->id}/approve")
            ->assertStatus(403);

        $this->assertDatabaseHas('article_comments', ['id' => $comment->id, 'status' => 'pending']);
    }

    public function test_admin_can_approve_comment(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);
        $comment = $article->comments()->create([
            'name' => 'زائر',
            'comment' => 'تعليق.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/article-comments/{$comment->id}/approve");

        $response->assertOk();
        $this->assertDatabaseHas('article_comments', [
            'id' => $comment->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_comment(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);
        $comment = $article->comments()->create([
            'name' => 'زائر',
            'comment' => 'تعليق مسيء.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/article-comments/{$comment->id}/reject");

        $response->assertOk();
        $this->assertDatabaseHas('article_comments', [
            'id' => $comment->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reply_to_comment(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);
        $comment = $article->comments()->create([
            'name' => 'زائر',
            'comment' => 'سؤال عن المقال.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/article-comments/{$comment->id}/reply", [
            'admin_reply' => 'شكرًا لتعليقك، سنوضح ذلك قريبًا.',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('article_comments', [
            'id' => $comment->id,
            'admin_reply' => 'شكرًا لتعليقك، سنوضح ذلك قريبًا.',
            'replied_by' => $admin->id,
        ]);
    }

    public function test_admin_can_delete_reply_without_deleting_comment(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);
        $comment = $article->comments()->create([
            'name' => 'زائر',
            'comment' => 'تعليق.',
            'status' => 'approved',
            'admin_reply' => 'رد سابق.',
            'replied_by' => $admin->id,
            'replied_at' => now(),
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/admin/article-comments/{$comment->id}/reply");

        $response->assertOk();
        $this->assertDatabaseHas('article_comments', [
            'id' => $comment->id,
            'admin_reply' => null,
            'replied_by' => null,
        ]);
    }

    public function test_admin_can_delete_comment(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle($admin);
        $comment = $article->comments()->create([
            'name' => 'زائر',
            'comment' => 'تعليق للحذف.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/admin/article-comments/{$comment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('article_comments', ['id' => $comment->id]);
    }
}
