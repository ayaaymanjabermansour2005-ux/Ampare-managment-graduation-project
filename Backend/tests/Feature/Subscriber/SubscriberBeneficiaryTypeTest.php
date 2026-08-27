<?php

namespace Tests\Feature\Subscriber;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberBeneficiaryTypeTest extends TestCase
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

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN->value);

        return $admin;
    }

    /**
     * @return array{0: Subscriber, 1: User}
     */
    private function makeConnectedSubscriber(User $owner): array
    {
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $user->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
        ]);

        return [$subscriber, $user];
    }

    public function test_subscriber_defaults_to_normal_beneficiary_type(): void
    {
        $user = User::factory()->create();
        $subscriber = Subscriber::factory()->create(['user_id' => $user->id]);

        $this->assertSame('normal', $subscriber->fresh()->beneficiary_type->value);
        $this->assertNull($subscriber->beneficiary_type_changed_by);
        $this->assertNull($subscriber->beneficiary_type_changed_at);
    }

    public function test_owner_can_update_beneficiary_type_for_connected_subscriber(): void
    {
        $owner = $this->makeOwner();
        [$subscriber] = $this->makeConnectedSubscriber($owner);

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/subscribers/{$subscriber->id}/beneficiary-type", [
                'beneficiary_type' => 'special',
            ]);

        $response->assertOk();
        $this->assertSame('special', $response->json('data.beneficiary_type'));

        $fresh = $subscriber->fresh();
        $this->assertSame('special', $fresh->beneficiary_type->value);
        $this->assertSame($owner->id, $fresh->beneficiary_type_changed_by);
        $this->assertNotNull($fresh->beneficiary_type_changed_at);
    }

    public function test_owner_cannot_update_beneficiary_type_for_unrelated_subscriber(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        [$subscriber] = $this->makeConnectedSubscriber($otherOwner);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscribers/{$subscriber->id}/beneficiary-type", [
                'beneficiary_type' => 'special',
            ])
            ->assertStatus(403);

        $this->assertSame('normal', $subscriber->fresh()->beneficiary_type->value);
    }

    public function test_admin_cannot_update_beneficiary_type_directly(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [$subscriber] = $this->makeConnectedSubscriber($owner);

        $this->actingAs($admin)
            ->patchJson("/api/v1/subscribers/{$subscriber->id}/beneficiary-type", [
                'beneficiary_type' => 'special',
            ])
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_update_own_beneficiary_type(): void
    {
        $owner = $this->makeOwner();
        [$subscriber, $subscriberUser] = $this->makeConnectedSubscriber($owner);

        $this->actingAs($subscriberUser)
            ->patchJson("/api/v1/subscribers/{$subscriber->id}/beneficiary-type", [
                'beneficiary_type' => 'special',
            ])
            ->assertStatus(403);
    }

    public function test_beneficiary_type_must_be_valid_value(): void
    {
        $owner = $this->makeOwner();
        [$subscriber] = $this->makeConnectedSubscriber($owner);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscribers/{$subscriber->id}/beneficiary-type", [
                'beneficiary_type' => 'vip',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('beneficiary_type');
    }

    public function test_unauthenticated_user_cannot_update_beneficiary_type(): void
    {
        $owner = $this->makeOwner();
        [$subscriber] = $this->makeConnectedSubscriber($owner);

        $this->patchJson("/api/v1/subscribers/{$subscriber->id}/beneficiary-type", [
            'beneficiary_type' => 'special',
        ])->assertStatus(401);
    }
}
