<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\ContactMessage;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarBadgeTest extends TestCase
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

    public function test_guest_cannot_view_sidebar_badge_counts(): void
    {
        $this->getJson('/api/v1/admin/sidebar/badge-counts')->assertStatus(401);
    }

    public function test_non_admin_cannot_view_sidebar_badge_counts(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/admin/sidebar/badge-counts')
            ->assertStatus(403);
    }

    public function test_admin_can_view_sidebar_badge_counts(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/sidebar/badge-counts');

        $response->assertOk();
        $data = $response->json('data');
        foreach ([
            'owner_applications_pending',
            'payments_pending',
            'complaints_open',
            'faults_pending',
            'contact_messages_new',
            'generators_pending_verification',
            'users_locked',
            'invoices_overdue',
            'article_comments_pending',
        ] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    public function test_badge_counts_reflect_pending_contact_messages(): void
    {
        $admin = $this->makeAdmin();
        ContactMessage::create([
            'name' => 'زائر',
            'phone' => '0599123456',
            'email' => 'guest@example.com',
            'subject' => 'general',
            'message' => 'رسالة.',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/sidebar/badge-counts');

        $response->assertOk();
        $this->assertSame(1, $response->json('data.contact_messages_new'));
    }
}
