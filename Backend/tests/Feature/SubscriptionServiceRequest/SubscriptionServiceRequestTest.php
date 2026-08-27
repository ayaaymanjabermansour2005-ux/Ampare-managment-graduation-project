<?php

namespace Tests\Feature\SubscriptionServiceRequest;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\SubscriptionServiceRequest;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceRequestTest extends TestCase
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

    private function makeActiveSubscription(User $owner, ?Generator $generator = null): array
    {
        $generator ??= Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $user->id]);
        $meter = SubscriberMeter::factory()->create([
            'subscriber_id' => $subscriber->id,
            'status' => 'active',
        ]);

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        return [$user, $subscription];
    }

    private function validPayload(Subscription $subscription, array $overrides = []): array
    {
        return array_merge([
            'subscription_id' => $subscription->id,
            'request_type' => 'event',
            'event_type' => 'wedding',
            'description' => 'عرس بالمنزل، إنارة خارجية وسماعات.',
            'extra_capacity_kw' => 10,
            'starts_at' => now()->addDays(3)->toDateTimeString(),
            'ends_at' => now()->addDays(3)->addHours(6)->toDateTimeString(),
        ], $overrides);
    }

    public function test_subscriber_can_create_service_request_for_own_active_subscription(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/subscription-service-requests', $this->validPayload($subscription));

        $response->assertStatus(201);
        $this->assertSame('pending', $response->json('data.status'));
        $this->assertDatabaseHas('subscription_service_requests', [
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_cannot_create_service_request(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);

        $this->actingAs($owner)
            ->postJson('/api/v1/subscription-service-requests', $this->validPayload($subscription))
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_create_request_for_inactive_subscription(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);
        $subscription->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($user)
            ->postJson('/api/v1/subscription-service-requests', $this->validPayload($subscription))
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscription_id');
    }

    public function test_subscriber_cannot_create_request_for_another_subscribers_subscription(): void
    {
        $owner = $this->makeOwner();
        [, $subscription] = $this->makeActiveSubscription($owner);
        [$otherUser] = $this->makeActiveSubscription($owner);

        $this->actingAs($otherUser)
            ->postJson('/api/v1/subscription-service-requests', $this->validPayload($subscription))
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscription_id');
    }

    public function test_event_type_required_when_request_type_is_event(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $payload = $this->validPayload($subscription);
        unset($payload['event_type']);

        $this->actingAs($user)
            ->postJson('/api/v1/subscription-service-requests', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('event_type');
    }

    public function test_event_type_is_dropped_when_request_type_is_not_event(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/subscription-service-requests', $this->validPayload($subscription, [
                'request_type' => 'maintenance',
                'event_type' => 'wedding',
            ]));

        $response->assertStatus(201);
        $this->assertNull($response->json('data.event_type'));
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $this->actingAs($user)
            ->postJson('/api/v1/subscription-service-requests', $this->validPayload($subscription, [
                'starts_at' => now()->addDays(3)->toDateTimeString(),
                'ends_at' => now()->addDays(2)->toDateTimeString(),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_starts_at_must_be_in_future(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $this->actingAs($user)
            ->postJson('/api/v1/subscription-service-requests', $this->validPayload($subscription, [
                'starts_at' => now()->subDay()->toDateTimeString(),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('starts_at');
    }

    public function test_owner_can_approve_request_without_fee_or_capacity(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة دورية.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'approved',
            ])
            ->assertOk();

        $this->assertSame('approved', $serviceRequest->fresh()->status->value);
        $this->assertDatabaseMissing('subscription_overrides', ['service_request_id' => $serviceRequest->id]);
        $this->assertDatabaseMissing('invoices', ['service_request_id' => $serviceRequest->id]);
    }

    public function test_approving_request_with_extra_capacity_creates_override(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $startsAt = now()->addDays(3);
        $endsAt = now()->addDays(3)->addHours(6);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'event',
            'event_type' => 'wedding',
            'description' => 'عرس.',
            'extra_capacity_kw' => 10,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'approved',
            ])
            ->assertOk();

        $this->assertDatabaseHas('subscription_overrides', [
            'service_request_id' => $serviceRequest->id,
            'subscription_id' => $subscription->id,
            'extra_capacity_kw' => '10.00',
        ]);
    }

    public function test_approving_request_without_extra_capacity_does_not_create_override(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة بدون زيادة سعة.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'approved',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('subscription_overrides', ['service_request_id' => $serviceRequest->id]);
    }

    public function test_approving_request_with_fee_creates_invoice_and_commission(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'event',
            'event_type' => 'wedding',
            'description' => 'عرس.',
            'extra_capacity_kw' => 10,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(6),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'approved',
                'fee_amount' => 200,
                'fee_currency' => 'ILS',
            ]);

        $response->assertOk();
        $this->assertNotNull($response->json('data.invoice.id'));
        $this->assertEquals(200.0, $response->json('data.invoice.final_amount'));

        $this->assertDatabaseHas('invoices', [
            'service_request_id' => $serviceRequest->id,
            'subscription_id' => $subscription->id,
            'meter_reading_id' => null,
            'final_amount' => '200.00',
            'status' => 'pending',
        ]);

        $invoiceId = $response->json('data.invoice.id');

        $this->assertDatabaseHas('platform_commissions', [
            'invoice_id' => $invoiceId,
            'owner_id' => $owner->id,
            'commission_rate' => '10.00',
            'commission_amount' => '20.00',
        ]);
    }

    public function test_approving_request_without_fee_does_not_create_invoice(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'extra_hours',
            'description' => 'ساعات إضافية بدون رسوم.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(3),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'approved',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('invoices', ['service_request_id' => $serviceRequest->id]);
    }

    public function test_rejecting_request_does_not_create_override_or_invoice(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'event',
            'event_type' => 'wedding',
            'description' => 'عرس.',
            'extra_capacity_kw' => 10,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(6),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'rejected',
                'review_note' => 'لا توجد سعة كافية بالمولد بهذا التاريخ.',
            ])
            ->assertOk();

        $fresh = $serviceRequest->fresh();
        $this->assertSame('rejected', $fresh->status->value);
        $this->assertNull($fresh->fee_amount);
        $this->assertDatabaseMissing('subscription_overrides', ['service_request_id' => $serviceRequest->id]);
        $this->assertDatabaseMissing('invoices', ['service_request_id' => $serviceRequest->id]);
    }

    public function test_admin_can_review_any_service_request(): void
    {
        $owner = $this->makeOwner();
        $admin = $this->makeAdmin();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'approved',
            ])
            ->assertOk();

        $this->assertSame($admin->id, $serviceRequest->fresh()->reviewed_by);
    }

    public function test_owner_cannot_review_another_owners_service_request(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($otherOwner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'approved',
            ])
            ->assertStatus(403);
    }

    public function test_cannot_review_already_reviewed_request(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'approved',
            'reviewed_by' => $owner->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'rejected',
            ])
            ->assertStatus(403);
    }

    public function test_fee_currency_required_when_fee_amount_given(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'event',
            'event_type' => 'wedding',
            'description' => 'عرس.',
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(6),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/review", [
                'decision' => 'approved',
                'fee_amount' => 150,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('fee_currency');
    }

    public function test_requester_can_cancel_own_pending_request(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/cancel")
            ->assertOk();

        $this->assertSame('cancelled', $serviceRequest->fresh()->status->value);
    }

    public function test_cannot_cancel_already_approved_request(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'approved',
            'reviewed_by' => $owner->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($user)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/cancel")
            ->assertStatus(403);
    }

    public function test_other_subscriber_cannot_cancel_someone_elses_request(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);
        [$otherUser] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
        ]);

        $this->actingAs($otherUser)
            ->patchJson("/api/v1/subscription-service-requests/{$serviceRequest->id}/cancel")
            ->assertStatus(403);

        $this->assertSame('pending', $serviceRequest->fresh()->status->value);
    }

    public function test_owner_cannot_view_another_owners_service_request(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($otherOwner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/subscription-service-requests/{$serviceRequest->id}")
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_view_another_subscribers_service_request(): void
    {
        $owner = $this->makeOwner();
        [$user, $subscription] = $this->makeActiveSubscription($owner);
        [$otherUser] = $this->makeActiveSubscription($owner);

        $serviceRequest = SubscriptionServiceRequest::factory()->create([
            'subscription_id' => $subscription->id,
            'requested_by' => $user->id,
            'request_type' => 'maintenance',
            'description' => 'صيانة.',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
        ]);

        $this->actingAs($otherUser)
            ->getJson("/api/v1/subscription-service-requests/{$serviceRequest->id}")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_service_requests(): void
    {
        $this->getJson('/api/v1/subscription-service-requests')->assertStatus(401);
    }
}
