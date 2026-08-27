<?php

namespace Tests\Feature\Attachment;

use App\Enums\Role as RoleEnum;
use App\Models\Attachment;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        Storage::fake('attachments');
    }

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    private function makeSubscriberUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * @return array{0: Attachment, 1: Generator} مرفق فعلي مرفوع على مولد الأونر
     */
    private function uploadGeneratorAttachment(User $owner): array
    {
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $file = UploadedFile::fake()->create('license.pdf', 100, 'application/pdf');

        $response = $this->actingAs($owner)
            ->postJson("/api/v1/generators/{$generator->id}/attachments", [
                'document_type' => 'generator_license',
                'file' => $file,
            ]);

        $response->assertStatus(201);

        $attachment = Attachment::findOrFail($response->json('data.id'));

        return [$attachment, $generator];
    }

    public function test_uploader_can_view_own_attachment(): void
    {
        $owner = $this->makeOwner();
        [$attachment] = $this->uploadGeneratorAttachment($owner);

        $this->actingAs($owner)
            ->getJson("/api/v1/attachments/{$attachment->id}")
            ->assertOk();
    }

    public function test_admin_can_view_any_attachment(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$attachment] = $this->uploadGeneratorAttachment($owner);

        $this->actingAs($admin)
            ->getJson("/api/v1/attachments/{$attachment->id}")
            ->assertOk();
    }

    public function test_unrelated_user_cannot_view_attachment(): void
    {
        $owner = $this->makeOwner();
        [$attachment] = $this->uploadGeneratorAttachment($owner);

        $otherOwner = $this->makeOwner();

        $this->actingAs($otherOwner)
            ->getJson("/api/v1/attachments/{$attachment->id}")
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_view_unrelated_generator_attachment(): void
    {
        $owner = $this->makeOwner();
        [$attachment] = $this->uploadGeneratorAttachment($owner);

        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/attachments/{$attachment->id}")
            ->assertStatus(403);
    }

    public function test_owner_can_download_own_generator_attachment(): void
    {
        $owner = $this->makeOwner();
        [$attachment] = $this->uploadGeneratorAttachment($owner);

        $this->actingAs($owner)
            ->get("/api/v1/attachments/{$attachment->id}/download")
            ->assertOk();
    }

    public function test_uploader_can_delete_own_attachment(): void
    {
        $owner = $this->makeOwner();
        [$attachment] = $this->uploadGeneratorAttachment($owner);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/attachments/{$attachment->id}")
            ->assertOk();

        $this->assertSoftDeleted('attachments', ['id' => $attachment->id]);
    }

    public function test_admin_can_delete_any_attachment(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$attachment] = $this->uploadGeneratorAttachment($owner);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/attachments/{$attachment->id}")
            ->assertOk();
    }

    public function test_unrelated_user_cannot_delete_attachment(): void
    {
        $owner = $this->makeOwner();
        [$attachment] = $this->uploadGeneratorAttachment($owner);

        $otherOwner = $this->makeOwner();

        $this->actingAs($otherOwner)
            ->deleteJson("/api/v1/attachments/{$attachment->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('attachments', ['id' => $attachment->id, 'deleted_at' => null]);
    }

    public function test_unauthenticated_user_cannot_access_attachments(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $attachment = $generator->attachments()->create([
            'uploaded_by' => $owner->id,
            'document_type' => 'generator_license',
            'original_name' => 'license.pdf',
            'stored_name' => Str::uuid().'.pdf',
            'disk' => 'attachments',
            'path' => 'attachments/generator/fake-file.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
        ]);

        $this->getJson("/api/v1/attachments/{$attachment->id}")->assertStatus(401);
    }
}
