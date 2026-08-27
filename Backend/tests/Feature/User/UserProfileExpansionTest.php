<?php

namespace Tests\Feature\User;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        Storage::fake('public');
    }

    private function makeSubscriber(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        return $user;
    }

    public function test_user_can_update_extended_profile_fields(): void
    {
        $user = $this->makeSubscriber();

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/users/{$user->id}", [
                'birth_date' => '1995-05-20',
                'address' => 'حي الرمال، غزة',
                'latitude' => 31.5,
                'longitude' => 34.45,
                'bio' => 'نبذة تعريفية قصيرة.',
                'whatsapp' => '+970599123456',
                'facebook_url' => 'https://facebook.com/test',
                'instagram_url' => 'https://instagram.com/test',
            ]);

        $response->assertOk();
        $this->assertSame('1995-05-20', $response->json('data.birth_date'));
        $this->assertSame('حي الرمال، غزة', $response->json('data.address'));
        $this->assertEquals(31.5, $response->json('data.location.latitude'));
        $this->assertSame('+970599123456', $response->json('data.whatsapp'));
    }

    public function test_birth_date_must_be_in_the_past(): void
    {
        $user = $this->makeSubscriber();

        $this->actingAs($user)
            ->patchJson("/api/v1/users/{$user->id}", ['birth_date' => now()->addYear()->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('birth_date');
    }

    public function test_user_can_upload_avatar(): void
    {
        $user = $this->makeSubscriber();

        $response = $this->actingAs($user)
            ->post("/api/v1/users/{$user->id}/avatar", [
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response->assertOk();
        $this->assertNotNull($response->json('data.avatar_url'));
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_uploading_new_avatar_deletes_old_one(): void
    {
        $user = $this->makeSubscriber();

        $this->actingAs($user)->post("/api/v1/users/{$user->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('first.jpg'),
        ]);
        $oldPath = $user->fresh()->avatar_path;

        $this->actingAs($user)->post("/api/v1/users/{$user->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('second.jpg'),
        ]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_non_image_file_is_rejected_as_avatar(): void
    {
        $user = $this->makeSubscriber();

        $this->actingAs($user)
            ->post("/api/v1/users/{$user->id}/avatar", [
                'avatar' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422);
    }

    public function test_user_cannot_update_another_users_avatar(): void
    {
        $user = $this->makeSubscriber();
        $other = $this->makeSubscriber();

        $this->actingAs($user)
            ->post("/api/v1/users/{$other->id}/avatar", [
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertStatus(403);
    }
}
