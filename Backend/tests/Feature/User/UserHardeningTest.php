<?php

namespace Tests\Feature\User;

use App\Enums\Role as RoleEnum;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserHardeningTest extends TestCase
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

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }

    public function test_admin_can_create_generator_owner_account(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/users/generator-owners', [
                'name' => 'مالك جديد',
                'email' => 'newowner@example.com',
                'phone' => '+970599111222',
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ]);

        $response->assertStatus(201);

        $newOwner = User::where('email', 'newowner@example.com')->first();
        $this->assertNotNull($newOwner);
        $this->assertTrue($newOwner->hasRole(RoleEnum::GENERATOR_OWNER->value));
        $this->assertNotNull($newOwner->email_verified_at);
    }

    public function test_non_admin_cannot_create_generator_owner_account(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/users/generator-owners', [
                'name' => 'محاولة',
                'email' => 'attempt@example.com',
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ])
            ->assertStatus(403);
    }

    public function test_create_generator_owner_rejects_duplicate_email(): void
    {
        $admin = $this->makeAdmin();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->postJson('/api/v1/users/generator-owners', [
                'name' => 'محاولة',
                'email' => 'taken@example.com',
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_can_assign_plan_to_owner(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $plan = Plan::create([
            'name' => 'خطة الاختبار',
            'code' => 'test-'.Str::random(6),
            'max_generators' => 3,
            'price_monthly' => 100,
            'currency' => 'ILS',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$owner->id}/plan", ['plan_id' => $plan->id])
            ->assertOk();

        $this->assertSame($plan->id, $owner->fresh()->plan_id);
    }

    public function test_non_admin_cannot_assign_plan(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $plan = Plan::create([
            'name' => 'خطة الاختبار',
            'code' => 'test2-'.Str::random(6),
            'max_generators' => 3,
            'price_monthly' => 100,
            'currency' => 'ILS',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$otherOwner->id}/plan", ['plan_id' => $plan->id])
            ->assertStatus(403);
    }

    public function test_technician_cannot_list_users(): void
    {
        $technician = User::factory()->create();
        $technician->assignRole(RoleEnum::TECHNICIAN->value);

        $this->actingAs($technician)
            ->getJson('/api/v1/users')
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_list_users(): void
    {
        $subscriber = User::factory()->create();
        $subscriber->assignRole(RoleEnum::SUBSCRIBER->value);

        $this->actingAs($subscriber)
            ->getJson('/api/v1/users')
            ->assertStatus(403);
    }
}
