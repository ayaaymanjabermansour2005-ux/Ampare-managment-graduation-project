<?php

namespace Tests\Feature\PaymentMethod;

use App\Enums\Role as RoleEnum;
use App\Models\PaymentMethod;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
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

    public function test_owner_can_create_bank_payment_method(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/payment-methods', $this->bankPayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('payment_methods', [
            'user_id' => $owner->id,
            'type' => 'bank',
            'currency' => 'ILS',
        ]);
    }

    public function test_owner_can_create_cash_payment_method_without_currency_or_account_details(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/payment-methods', ['type' => 'cash']);

        $response->assertStatus(201);
        $this->assertNull($response->json('data.currency'));
    }

    public function test_bank_type_requires_currency(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/payment-methods', $this->bankPayload(['currency' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('currency');
    }

    public function test_cash_type_rejects_currency(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/payment-methods', ['type' => 'cash', 'currency' => 'ILS'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('currency');
    }

    public function test_bank_type_requires_account_details(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/payment-methods', $this->bankPayload(['bank_name' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('bank_name');
    }

    public function test_subscriber_cannot_create_payment_method(): void
    {
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/payment-methods', ['type' => 'cash'])
            ->assertStatus(403);
    }

    public function test_creating_new_default_unsets_previous_default(): void
    {
        $owner = $this->makeOwner();

        $first = $this->actingAs($owner)
            ->postJson('/api/v1/payment-methods', $this->bankPayload(['is_default' => true]))
            ->json('data.id');

        $this->actingAs($owner)
            ->postJson('/api/v1/payment-methods', array_merge(
                $this->bankPayload(['is_default' => true]),
                ['account_number' => '9999999999']
            ))
            ->assertStatus(201);

        $this->assertFalse((bool) PaymentMethod::find($first)->is_default);
    }

    public function test_owner_can_update_own_payment_method(): void
    {
        $owner = $this->makeOwner();
        $method = PaymentMethod::factory()->create([
            'user_id' => $owner->id,
            'type' => 'bank',
            'currency' => 'ILS',
            'bank_name' => 'بنك فلسطين',
        ]);

        $this->actingAs($owner)
            ->putJson("/api/v1/payment-methods/{$method->id}", ['account_name' => 'اسم محدّث'])
            ->assertOk();

        $this->assertSame('اسم محدّث', $method->fresh()->account_name);
    }

    public function test_owner_cannot_update_another_owners_payment_method(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $method = PaymentMethod::factory()->create([
            'user_id' => $otherOwner->id,
            'type' => 'bank',
            'currency' => 'ILS',
            'bank_name' => 'بنك فلسطين',
        ]);

        $this->actingAs($owner)
            ->putJson("/api/v1/payment-methods/{$method->id}", ['account_name' => 'محاولة'])
            ->assertStatus(403);
    }

    public function test_admin_can_update_any_payment_method(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $method = PaymentMethod::factory()->create([
            'user_id' => $owner->id,
            'type' => 'bank',
            'currency' => 'ILS',
            'bank_name' => 'بنك فلسطين',
        ]);

        $this->actingAs($admin)
            ->putJson("/api/v1/payment-methods/{$method->id}", ['account_name' => 'تعديل إداري'])
            ->assertOk();
    }

    public function test_setting_default_via_update_unsets_other_defaults(): void
    {
        $owner = $this->makeOwner();
        $first = PaymentMethod::factory()->create(['user_id' => $owner->id, 'type' => 'cash', 'is_default' => true]);
        $second = PaymentMethod::factory()->create(['user_id' => $owner->id, 'type' => 'cash', 'is_default' => false]);

        $this->actingAs($owner)
            ->putJson("/api/v1/payment-methods/{$second->id}", ['is_default' => true])
            ->assertOk();

        $this->assertFalse((bool) $first->fresh()->is_default);
        $this->assertTrue((bool) $second->fresh()->is_default);
    }

    public function test_changing_type_to_cash_nullifies_currency(): void
    {
        $owner = $this->makeOwner();
        $method = PaymentMethod::factory()->create([
            'user_id' => $owner->id,
            'type' => 'bank',
            'currency' => 'ILS',
            'bank_name' => 'بنك فلسطين',
        ]);

        $this->actingAs($owner)
            ->putJson("/api/v1/payment-methods/{$method->id}", ['type' => 'cash'])
            ->assertOk();

        $this->assertNull($method->fresh()->currency);
    }

    public function test_owner_can_delete_own_payment_method(): void
    {
        $owner = $this->makeOwner();
        $method = PaymentMethod::factory()->create(['user_id' => $owner->id, 'type' => 'cash']);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/payment-methods/{$method->id}")
            ->assertOk();
    }

    public function test_owner_cannot_delete_another_owners_payment_method(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $method = PaymentMethod::factory()->create(['user_id' => $otherOwner->id, 'type' => 'cash']);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/payment-methods/{$method->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_delete_any_payment_method(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $method = PaymentMethod::factory()->create(['user_id' => $owner->id, 'type' => 'cash']);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/payment-methods/{$method->id}")
            ->assertOk();
    }

    public function test_index_scoped_to_own_methods_for_owner(): void
    {
        $owner = $this->makeOwner();
        $ownMethod = PaymentMethod::factory()->create(['user_id' => $owner->id, 'type' => 'cash']);

        $otherOwner = $this->makeOwner();
        PaymentMethod::factory()->create(['user_id' => $otherOwner->id, 'type' => 'cash']);

        $response = $this->actingAs($owner)
            ->getJson('/api/v1/payment-methods');

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($ownMethod->id));
        $this->assertCount(1, $ids);
    }

    public function test_admin_sees_all_payment_methods(): void
    {
        $admin = $this->makeAdmin();
        $owner1 = $this->makeOwner();
        $owner2 = $this->makeOwner();
        PaymentMethod::factory()->create(['user_id' => $owner1->id, 'type' => 'cash']);
        PaymentMethod::factory()->create(['user_id' => $owner2->id, 'type' => 'cash']);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/payment-methods');

        $response->assertOk();

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_subscriber_cannot_list_payment_methods(): void
    {
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->getJson('/api/v1/payment-methods')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_payment_methods(): void
    {
        $this->getJson('/api/v1/payment-methods')->assertStatus(401);
    }
}
