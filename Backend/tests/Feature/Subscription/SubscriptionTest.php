<?php

namespace Tests\Feature\Subscription;

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
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

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

    private function makeSubscriberUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        $subscriber = Subscriber::factory()->create(['user_id' => $user->id]);
        $meter = SubscriberMeter::factory()->create([
            'subscriber_id' => $subscriber->id,
            'status' => 'active',
        ]);

        return [$user, $subscriber, $meter];
    }

    private function makeGenerator(User $owner, array $overrides = []): Generator
    {
        return Generator::factory()->create(array_merge([
            'owner_id' => $owner->id,
            'operating_schedule' => '24h',
            'capacity_kw' => 100,
            'status' => 'active',
        ], $overrides));
    }

    private function validPayload(Generator $generator, SubscriberMeter $meter, array $overrides = []): array
    {
        return array_merge([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'requested_capacity_kw' => 10,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'start_date' => now()->toDateString(),
        ], $overrides);
    }

    /**
     * @return array{0: User, 1: Subscription, 2: SubscriberMeter}
     */
    private function makeSubscriptionScenario(array $subscriptionOverrides = []): array
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create([
            'owner_id' => $owner->id,
            'operating_schedule' => '24h',
        ]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);

        $subscription = Subscription::factory()->create(array_merge([
            'generator_id' => $generator->id,
            'subscriber_meter_id' => $meter->id,
            'schedule' => '24h',
            'service_start_time' => null,
            'service_end_time' => null,
            'status' => 'active',
        ], $subscriptionOverrides));

        return [$subscriberUser, $subscription, $meter];
    }

    public function test_subscriber_can_create_subscription_request_with_pending_status(): void
    {
        [$user,, $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $meter));

        $response->assertStatus(201);
        $this->assertSame('pending', $response->json('data.status'));
        $this->assertDatabaseHas('subscriptions', [
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_cannot_create_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [,, $meter] = $this->makeSubscriberUser();

        $this->actingAs($owner)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $meter))
            ->assertStatus(403);
    }

    public function test_cannot_subscribe_using_inactive_meter(): void
    {
        [$user, $subscriber] = $this->makeSubscriberUser();
        $inactiveMeter = SubscriberMeter::factory()->create([
            'subscriber_id' => $subscriber->id,
            'status' => 'inactive',
        ]);
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $inactiveMeter))
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscriber_meter_id');
    }

    public function test_cannot_subscribe_using_another_subscribers_meter(): void
    {
        [$user] = $this->makeSubscriberUser();
        [,, $otherMeter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $otherMeter))
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscriber_meter_id');
    }

    public function test_cannot_subscribe_to_inactive_generator(): void
    {
        [$user,, $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner, ['status' => 'maintenance']);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $meter))
            ->assertStatus(422)
            ->assertJsonValidationErrors('generator_id');
    }

    public function test_cannot_subscribe_with_schedule_generator_does_not_serve(): void
    {
        [$user,, $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner, ['operating_schedule' => 'day']);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $meter, ['schedule' => 'night']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('generator_id');
    }

    public function test_cannot_create_duplicate_contract_for_same_meter_generator_and_schedule(): void
    {
        [$user,, $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $meter, ['schedule' => 'day']))
            ->assertStatus(409)
            ->assertJsonValidationErrors('generator_id');
    }

    public function test_can_create_second_contract_on_same_meter_with_different_schedule(): void
    {
        [$user,, $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $meter, [
                'schedule' => 'night',
                'requested_capacity_kw' => 5,
            ]))
            ->assertStatus(201);
    }

    public function test_cannot_exceed_generator_capacity_on_overlapping_schedule(): void
    {
        [,, $existingMeter] = $this->makeSubscriberUser();
        [$user,, $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner, ['capacity_kw' => 10]);

        Subscription::factory()->create([
            'subscriber_meter_id' => $existingMeter->id,
            'generator_id' => $generator->id,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'requested_capacity_kw' => 8,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $meter, [
                'schedule' => 'day',
                'requested_capacity_kw' => 5,
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('requested_capacity_kw');

        $this->assertDatabaseMissing('subscriptions', [
            'subscriber_meter_id' => $meter->id,
        ]);
    }

    public function test_non_overlapping_day_and_night_schedules_do_not_share_capacity(): void
    {
        [,, $existingMeter] = $this->makeSubscriberUser();
        [$user,, $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner, ['capacity_kw' => 10]);

        Subscription::factory()->create([
            'subscriber_meter_id' => $existingMeter->id,
            'generator_id' => $generator->id,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'requested_capacity_kw' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $meter, [
                'schedule' => 'night',
                'requested_capacity_kw' => 10,
            ]))
            ->assertStatus(201);
    }

    public function test_capacity_check_is_skipped_when_no_requested_capacity_given(): void
    {
        [$user,, $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner, ['capacity_kw' => 1]);

        $payload = $this->validPayload($generator, $meter);
        unset($payload['requested_capacity_kw']);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $payload)
            ->assertStatus(201);
    }

    public function test_generator_with_null_capacity_accepts_any_requested_capacity(): void
    {
        [$user,, $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner, ['capacity_kw' => null]);

        $this->actingAs($user)
            ->postJson('/api/v1/subscriptions', $this->validPayload($generator, $meter, [
                'requested_capacity_kw' => 99999,
            ]))
            ->assertStatus(201);
    }

    public function test_owner_can_approve_pending_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'active'])
            ->assertOk();

        $this->assertSame('active', $subscription->fresh()->status->value);
    }

    public function test_owner_can_reject_pending_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'rejected'])
            ->assertOk();

        $this->assertSame('rejected', $subscription->fresh()->status->value);
    }

    public function test_owner_can_suspend_then_reactivate_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'schedule' => 'day',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'suspended'])
            ->assertOk();
        $this->assertSame('suspended', $subscription->fresh()->status->value);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'active'])
            ->assertOk();
        $this->assertSame('active', $subscription->fresh()->status->value);
    }

    public function test_owner_can_cancel_active_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'cancelled'])
            ->assertOk();

        $this->assertSame('cancelled', $subscription->fresh()->status->value);
    }

    public function test_cannot_transition_out_of_cancelled_status(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'cancelled',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'active'])
            ->assertStatus(422);

        $this->assertSame('cancelled', $subscription->fresh()->status->value);
    }

    public function test_cannot_transition_out_of_rejected_status(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'rejected',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'active'])
            ->assertStatus(422);
    }

    public function test_cannot_skip_directly_from_pending_to_suspended(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'suspended'])
            ->assertStatus(422);

        $this->assertSame('pending', $subscription->fresh()->status->value);
    }

    public function test_reactivating_suspended_subscription_rechecks_capacity(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner, ['capacity_kw' => 10]);

        [,, $suspendedMeter] = $this->makeSubscriberUser();
        $suspendedSubscription = Subscription::factory()->create([
            'subscriber_meter_id' => $suspendedMeter->id,
            'generator_id' => $generator->id,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'requested_capacity_kw' => 8,
            'status' => 'suspended',
        ]);

        [,, $otherMeter] = $this->makeSubscriberUser();
        Subscription::factory()->create([
            'subscriber_meter_id' => $otherMeter->id,
            'generator_id' => $generator->id,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'requested_capacity_kw' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$suspendedSubscription->id}/status", ['status' => 'active'])
            ->assertStatus(422);

        $this->assertSame('suspended', $suspendedSubscription->fresh()->status->value);
    }

    public function test_reactivating_suspended_subscription_succeeds_when_capacity_available(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner, ['capacity_kw' => 10]);

        [,, $suspendedMeter] = $this->makeSubscriberUser();
        $suspendedSubscription = Subscription::factory()->create([
            'subscriber_meter_id' => $suspendedMeter->id,
            'generator_id' => $generator->id,
            'schedule' => 'day',
            'billing_cycle' => 'monthly',
            'requested_capacity_kw' => 8,
            'status' => 'suspended',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$suspendedSubscription->id}/status", ['status' => 'active'])
            ->assertOk();

        $this->assertSame('active', $suspendedSubscription->fresh()->status->value);
    }

    public function test_subscriber_cannot_update_subscription_status(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [$user,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'active'])
            ->assertStatus(403);
    }

    public function test_owner_cannot_update_another_owners_subscription_status(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = $this->makeGenerator($otherOwner);
        [,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/status", ['status' => 'active'])
            ->assertStatus(403);
    }

    public function test_owner_cannot_view_another_owners_subscription(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $generator = $this->makeGenerator($otherOwner);
        [,, $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/subscriptions/{$subscription->id}")
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_view_another_subscribers_subscription(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [,, $meter] = $this->makeSubscriberUser();
        [$otherUser] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $this->actingAs($otherUser)
            ->getJson("/api/v1/subscriptions/{$subscription->id}")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_subscriptions(): void
    {
        $this->getJson('/api/v1/subscriptions')->assertStatus(401);
    }

    public function test_admin_can_transfer_subscription_to_compatible_generator(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription] = $this->makeSubscriptionScenario();

        $newOwner = $this->makeOwner();
        $newGenerator = Generator::factory()->create([
            'owner_id' => $newOwner->id,
            'operating_schedule' => '24h',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/transfer", [
                'generator_id' => $newGenerator->id,
            ]);

        $response->assertOk();
        $this->assertSame($newGenerator->id, $subscription->fresh()->generator_id);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $subscription->id,
            'description' => 'admin_transferred_subscription',
        ]);
    }

    public function test_owner_cannot_transfer_subscription(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeSubscriptionScenario();

        $newGenerator = Generator::factory()->create(['operating_schedule' => '24h']);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/transfer", [
                'generator_id' => $newGenerator->id,
            ])
            ->assertStatus(403);

        $this->assertNotSame($newGenerator->id, $subscription->fresh()->generator_id);
    }

    public function test_subscriber_cannot_transfer_subscription(): void
    {
        [$subscriberUser, $subscription] = $this->makeSubscriptionScenario();

        $newGenerator = Generator::factory()->create(['operating_schedule' => '24h']);

        $this->actingAs($subscriberUser)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/transfer", [
                'generator_id' => $newGenerator->id,
            ])
            ->assertStatus(403);
    }

    public function test_cannot_transfer_to_generator_with_incompatible_schedule(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription] = $this->makeSubscriptionScenario(['schedule' => '24h']);

        $incompatibleGenerator = Generator::factory()->create([
            'operating_schedule' => 'day',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/transfer", [
                'generator_id' => $incompatibleGenerator->id,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('generator_id');
        $this->assertNotSame($incompatibleGenerator->id, $subscription->fresh()->generator_id);
    }

    public function test_cannot_transfer_when_it_creates_duplicate_contract(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription, $meter] = $this->makeSubscriptionScenario(['schedule' => '24h']);

        $targetGenerator = Generator::factory()->create(['operating_schedule' => '24h']);

        Subscription::factory()->create([
            'generator_id' => $targetGenerator->id,
            'subscriber_meter_id' => $meter->id,
            'schedule' => '24h',
            'service_start_time' => null,
            'service_end_time' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/transfer", [
                'generator_id' => $targetGenerator->id,
            ]);

        $response->assertStatus(409);
        $this->assertNotSame($targetGenerator->id, $subscription->fresh()->generator_id);
    }

    public function test_transfer_requires_generator_id(): void
    {
        $admin = $this->makeAdmin();
        [, $subscription] = $this->makeSubscriptionScenario();

        $this->actingAs($admin)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}/transfer", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('generator_id');
    }

    public function test_unauthenticated_user_cannot_transfer_subscription(): void
    {
        [, $subscription] = $this->makeSubscriptionScenario();
        $newGenerator = Generator::factory()->create();

        $this->patchJson("/api/v1/subscriptions/{$subscription->id}/transfer", [
            'generator_id' => $newGenerator->id,
        ])->assertStatus(401);
    }

    /* ---------------------------------------------------------------
     | Contract PDF download (GAP 11 — real Arabic glyph shaping)
     |---------------------------------------------------------------*/

    public function test_subscriber_can_download_contract_pdf_for_own_active_subscription(): void
    {
        [$subscriberUser, $subscription] = $this->makeSubscriptionScenario(['status' => 'active']);

        $response = $this->actingAs($subscriberUser)
            ->get("/api/v1/subscriptions/{$subscription->id}/contract-pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_owner_can_download_contract_pdf_for_own_generators_subscription(): void
    {
        [, $subscription] = $this->makeSubscriptionScenario(['status' => 'active']);
        $owner = $subscription->generator->owner;

        $this->actingAs($owner)
            ->get("/api/v1/subscriptions/{$subscription->id}/contract-pdf")
            ->assertOk();
    }

    public function test_contract_pdf_rejects_pending_subscription(): void
    {
        [$subscriberUser, $subscription] = $this->makeSubscriptionScenario(['status' => 'pending']);

        $this->actingAs($subscriberUser)
            ->get("/api/v1/subscriptions/{$subscription->id}/contract-pdf")
            ->assertStatus(422);
    }

    public function test_unrelated_subscriber_cannot_download_contract_pdf(): void
    {
        [, $subscription] = $this->makeSubscriptionScenario(['status' => 'active']);
        [$otherUser] = $this->makeSubscriberUser();

        $this->actingAs($otherUser)
            ->get("/api/v1/subscriptions/{$subscription->id}/contract-pdf")
            ->assertStatus(403);
    }

    /* ---------------------------------------------------------------
     | Bilingual contract PDF (Arabic shaping preserved + real English)
     |---------------------------------------------------------------*/

    public function test_arabic_contract_pdf_still_renders_correctly(): void
    {
        [, $subscription] = $this->makeSubscriptionScenario(['status' => 'active']);

        app()->setLocale('ar');
        $service = app(\App\Services\Pdf\SubscriptionContractPdfService::class);
        $response = $service->stream($subscription->fresh());

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_english_contract_pdf_renders_with_translated_labels_and_ltr(): void
    {
        [, $subscription] = $this->makeSubscriptionScenario(['status' => 'active']);
        $subscription->loadMissing(['generator.owner', 'subscriberMeter.subscriber.user']);

        app()->setLocale('en');
        $html = view('pdf.subscription-contract', ['subscription' => $subscription])->render();

        $this->assertStringContainsString('<html lang="en" dir="ltr">', $html);
        $this->assertStringContainsString('Subscription contract #', $html);
        $this->assertStringContainsString('Agreed price per kW', $html);
        $this->assertStringContainsString('Contract status', $html);
        $this->assertStringContainsString('Active', $html);

        $this->assertStringNotContainsString('عقد اشتراك', $html);
        $this->assertStringNotContainsString('سعر الكيلوواط', $html);
        $this->assertStringNotContainsString('حالة العقد', $html);
    }

    public function test_english_contract_pdf_downloads_successfully_end_to_end(): void
    {
        [, $subscription] = $this->makeSubscriptionScenario(['status' => 'active']);

        app()->setLocale('en');
        $service = app(\App\Services\Pdf\SubscriptionContractPdfService::class);
        $response = $service->stream($subscription->fresh());

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_contract_pdf_route_honors_lang_query_param_for_locale(): void
    {
        [$subscriberUser, $subscription] = $this->makeSubscriptionScenario(['status' => 'active']);

        $response = $this->actingAs($subscriberUser)
            ->get("/api/v1/subscriptions/{$subscription->id}/contract-pdf?lang=en");

        $response->assertOk();
        $this->assertSame('en', app()->getLocale());
    }

    public function test_unauthenticated_user_cannot_download_contract_pdf(): void
    {
        [, $subscription] = $this->makeSubscriptionScenario(['status' => 'active']);

        $this->getJson("/api/v1/subscriptions/{$subscription->id}/contract-pdf")
            ->assertStatus(401);
    }
}
