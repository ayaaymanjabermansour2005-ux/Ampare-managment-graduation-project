<?php

namespace Tests\Feature\PaymentMethod;

use App\Enums\Role as RoleEnum;
use App\Models\PaymentMethod;
use App\Models\Subscriber;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianPaymentMethodTest extends TestCase
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

    private function makeTechnician(User $owner): Technician
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::TECHNICIAN->value);

        return Technician::factory()->create([
            'user_id' => $user->id,
            'owner_id' => $owner->id,
        ]);
    }

    private function makeSubscriberUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function bankPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'bank',
            'currency' => 'ILS',
            'bank_name' => 'بنك فلسطين',
            'account_name' => 'أحمد محمد',
            'account_number' => '1234567890',
        ], $overrides);
    }

    public function test_technician_can_create_own_payment_method(): void
    {
        $owner = $this->makeOwner();
        $technician = $this->makeTechnician($owner);

        $response = $this->actingAs($technician->user)
            ->postJson('/api/v1/payment-methods', $this->bankPayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('payment_methods', [
            'user_id' => $technician->user_id,
            'type' => 'bank',
            'currency' => 'ILS',
        ]);
    }

    public function test_technician_cannot_view_another_technicians_payment_methods(): void
    {
        $owner = $this->makeOwner();
        $technician = $this->makeTechnician($owner);
        PaymentMethod::factory()->create(['user_id' => $technician->user_id, 'type' => 'cash']);

        $otherOwner = $this->makeOwner();
        $otherTechnician = $this->makeTechnician($otherOwner);

        $this->actingAs($otherTechnician->user)
            ->getJson("/api/v1/payment-methods?technician_id={$technician->id}")
            ->assertStatus(422);
    }

    public function test_technician_cannot_view_unrelated_owners_payment_methods_via_technician_id(): void
    {
        $owner = $this->makeOwner();
        $technician = $this->makeTechnician($owner);

        $unrelatedOwner = $this->makeOwner();
        $unrelatedTechnician = $this->makeTechnician($unrelatedOwner);
        PaymentMethod::factory()->create(['user_id' => $unrelatedOwner->id, 'type' => 'cash']);

        $this->actingAs($technician->user)
            ->getJson("/api/v1/payment-methods?technician_id={$unrelatedTechnician->id}")
            ->assertStatus(422);
    }

    public function test_employing_owner_can_view_technicians_payment_method(): void
    {
        $owner = $this->makeOwner();
        $technician = $this->makeTechnician($owner);
        $method = PaymentMethod::factory()->create([
            'user_id' => $technician->user_id,
            'type' => 'bank',
            'currency' => 'ILS',
            'bank_name' => 'بنك فلسطين',
            'account_number' => '1234567890',
        ]);

        $response = $this->actingAs($owner)
            ->getJson("/api/v1/payment-methods?technician_id={$technician->id}");

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($method->id));
        $this->assertCount(1, $ids);

        // Account number must never be exposed raw.
        $this->assertArrayNotHasKey('account_number', $response->json('data.data.0'));
    }

    public function test_unrelated_owner_cannot_view_technicians_payment_method(): void
    {
        $owner = $this->makeOwner();
        $technician = $this->makeTechnician($owner);
        PaymentMethod::factory()->create(['user_id' => $technician->user_id, 'type' => 'cash']);

        $unrelatedOwner = $this->makeOwner();

        $this->actingAs($unrelatedOwner)
            ->getJson("/api/v1/payment-methods?technician_id={$technician->id}")
            ->assertStatus(422);
    }

    public function test_employing_owner_cannot_update_technicians_payment_method(): void
    {
        $owner = $this->makeOwner();
        $technician = $this->makeTechnician($owner);
        $method = PaymentMethod::factory()->create([
            'user_id' => $technician->user_id,
            'type' => 'bank',
            'currency' => 'ILS',
            'bank_name' => 'بنك فلسطين',
        ]);

        $this->actingAs($owner)
            ->putJson("/api/v1/payment-methods/{$method->id}", ['account_name' => 'محاولة تعديل'])
            ->assertStatus(403);
    }

    public function test_employing_owner_cannot_delete_technicians_payment_method(): void
    {
        $owner = $this->makeOwner();
        $technician = $this->makeTechnician($owner);
        $method = PaymentMethod::factory()->create(['user_id' => $technician->user_id, 'type' => 'cash']);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/payment-methods/{$method->id}")
            ->assertStatus(403);
    }

    public function test_subscriber_still_cannot_access_payment_methods_endpoints(): void
    {
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->getJson('/api/v1/payment-methods')
            ->assertStatus(403);

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/payment-methods', ['type' => 'cash'])
            ->assertStatus(403);

        $owner = $this->makeOwner();
        $method = PaymentMethod::factory()->create(['user_id' => $owner->id, 'type' => 'cash']);

        $this->actingAs($subscriberUser)
            ->putJson("/api/v1/payment-methods/{$method->id}", ['account_name' => 'محاولة'])
            ->assertStatus(403);

        $this->actingAs($subscriberUser)
            ->deleteJson("/api/v1/payment-methods/{$method->id}")
            ->assertStatus(403);
    }
}
