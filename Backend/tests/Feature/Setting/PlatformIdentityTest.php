<?php

namespace Tests\Feature\Setting;

use App\Enums\Role as RoleEnum;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_guest_can_view_public_platform_identity(): void
    {
        Setting::set('site_name', 'أمبير');
        Setting::set('about_page_content', 'من نحن...');
        Setting::set('maps_api_key', 'super-secret-key');

        $response = $this->getJson('/api/v1/platform-identity');

        $response->assertOk();
        $this->assertSame('أمبير', $response->json('data.site_name'));
        $this->assertSame('من نحن...', $response->json('data.about_page_content'));
        $this->assertArrayNotHasKey('maps_api_key', $response->json('data'));
    }

    public function test_admin_can_update_platform_identity_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/settings', [
                'settings' => [
                    'favicon_url' => 'https://example.com/favicon.ico',
                    'privacy_policy_content' => 'نص السياسة...',
                ],
            ])
            ->assertOk();

        $this->assertSame('https://example.com/favicon.ico', Setting::get('favicon_url'));
    }
}
