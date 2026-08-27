<?php

namespace Tests\Feature\UserPreference;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }

    private function makeSubscriber(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        return $user;
    }

    public function test_owner_gets_owner_specific_default_preferences(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)->getJson('/api/v1/preferences');

        $response->assertOk();
        $this->assertArrayHasKey('notify_fault_reported', $response->json('data'));
        $this->assertArrayHasKey('language', $response->json('data'));
        $this->assertTrue($response->json('data.notify_fault_reported'));
    }

    public function test_subscriber_does_not_see_owner_specific_preferences(): void
    {
        $subscriber = $this->makeSubscriber();

        $response = $this->actingAs($subscriber)->getJson('/api/v1/preferences');

        $response->assertOk();
        $this->assertArrayNotHasKey('notify_fault_reported', $response->json('data'));
        $this->assertArrayHasKey('notify_invoice_due_soon', $response->json('data'));
    }

    public function test_owner_can_update_own_preference(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->patchJson('/api/v1/preferences', [
                'preferences' => ['notify_fault_reported' => false, 'language' => 'en'],
            ])
            ->assertOk()
            ->assertJsonPath('data.notify_fault_reported', false)
            ->assertJsonPath('data.language', 'en');

        $this->assertFalse($this->actingAs($owner)->getJson('/api/v1/preferences')->json('data.notify_fault_reported'));
    }

    public function test_subscriber_cannot_set_owner_specific_preference(): void
    {
        $subscriber = $this->makeSubscriber();

        $this->actingAs($subscriber)
            ->patchJson('/api/v1/preferences', [
                'preferences' => ['notify_fault_reported' => false],
            ])
            ->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_access_preferences(): void
    {
        $this->getJson('/api/v1/preferences')->assertStatus(401);
    }
}
