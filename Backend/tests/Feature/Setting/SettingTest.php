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

class SettingTest extends TestCase
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

    public function test_sensitive_keys_are_masked_in_response(): void
    {
        $admin = $this->makeAdmin();
        Setting::set('sms_api_key', 'super-secret-real-key-12345');

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/settings');

        $response->assertOk();
        $this->assertArrayNotHasKey('super-secret-real-key-12345', $response->json());
        $this->assertSame('2345', substr($response->json('data.sms_api_key.masked_value'), -4));
        $this->assertTrue($response->json('data.sms_api_key.is_set'));
        $this->assertStringNotContainsString('super-secret-real-key-12345', $response->getContent());
    }

    public function test_non_sensitive_keys_still_show_full_value(): void
    {
        $admin = $this->makeAdmin();
        Setting::set('site_name', 'أمبير');

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/settings');

        $response->assertOk();
        $this->assertSame('أمبير', $response->json('data.site_name'));
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/settings', [
                'settings' => ['site_name' => 'اسم جديد', 'platform_fee_percentage' => 5],
            ])
            ->assertOk();

        $this->assertSame('اسم جديد', Setting::get('site_name'));
    }

    public function test_numeric_setting_rejects_non_numeric_value(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/settings', [
                'settings' => ['platform_fee_percentage' => 'not-a-number'],
            ])
            ->assertStatus(422);
    }

    public function test_numeric_setting_rejects_out_of_range_value(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/settings', [
                'settings' => ['platform_fee_percentage' => 150],
            ])
            ->assertStatus(422);
    }

    public function test_non_admin_cannot_view_settings(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        $this->actingAs($owner)
            ->getJson('/api/v1/admin/settings')
            ->assertStatus(403);
    }

    public function test_unknown_key_is_silently_ignored(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/settings', [
                'settings' => ['not_a_real_key' => 'value'],
            ])
            ->assertOk();

        $this->assertNull(Setting::get('not_a_real_key'));
    }
}
