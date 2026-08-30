<?php

namespace Tests\Feature\Subscription;

use App\Enums\Role as RoleEnum;
use App\Events\InvoiceDueSoon;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use App\Policies\SubscriptionPolicy;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OwnerSubscriptionTest extends TestCase
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

    private function makeGenerator(User $owner, array $overrides = []): Generator
    {
        return Generator::factory()->create(array_merge([
            'owner_id' => $owner->id,
            'operating_schedule' => '24h',
            'capacity_kw' => 100,
            'status' => 'active',
        ], $overrides));
    }

    /**
     * @return array{0: User, 1: Subscriber, 2: SubscriberMeter}
     */
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

    private function ownerPayload(Generator $generator, SubscriberMeter $meter, array $overrides = []): array
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

    /* ---------------------------------------------------------------
     | 1) Add Subscription (Owner-scoped)
     |---------------------------------------------------------------*/

    public function test_owner_can_create_subscription_on_own_generator_for_existing_subscriber(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [, , $meter] = $this->makeSubscriberUser();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/owner/subscriptions', $this->ownerPayload($generator, $meter));

        $response->assertStatus(201);
        $this->assertDatabaseHas('subscriptions', [
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_cannot_create_subscription_on_another_owners_generator(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $foreignGenerator = $this->makeGenerator($otherOwner);
        [, , $meter] = $this->makeSubscriberUser();

        // Blocked at the FormRequest layer (StoreOwnerSubscriptionRequest::withValidator),
        // which is the first line of defense and returns 422 with a clear message on
        // 'generator_id'. SubscriptionPolicy::createByOwner (403) is a defense-in-depth
        // backstop behind it — see the report for why 422 is the real HTTP outcome here.
        $response = $this->actingAs($owner)
            ->postJson('/api/v1/owner/subscriptions', $this->ownerPayload($foreignGenerator, $meter));

        $response->assertStatus(422)->assertJsonValidationErrors('generator_id');

        $this->assertDatabaseMissing('subscriptions', [
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $foreignGenerator->id,
        ]);
    }

    public function test_owner_policy_denies_create_by_owner_for_foreign_generator_directly(): void
    {
        // Unit-level check of the Policy ability itself (not reachable via the
        // HTTP endpoint in normal flow, since the FormRequest already rejects
        // first) — proves the 403 authorization boundary is real and correct.
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $foreignGenerator = $this->makeGenerator($otherOwner);
        $ownGenerator = $this->makeGenerator($owner);

        $policy = new SubscriptionPolicy;

        $this->assertFalse($policy->createByOwner($owner, $foreignGenerator));
        $this->assertTrue($policy->createByOwner($owner, $ownGenerator));
    }

    public function test_subscriber_cannot_use_owner_add_subscription_endpoint(): void
    {
        [$user, , $meter] = $this->makeSubscriberUser();
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        $this->actingAs($user)
            ->postJson('/api/v1/owner/subscriptions', $this->ownerPayload($generator, $meter))
            ->assertStatus(403);
    }

    /* ---------------------------------------------------------------
     | 2) Transfer Subscription (Owner-scoped)
     |---------------------------------------------------------------*/

    public function test_owner_can_transfer_subscription_between_own_generators(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $otherOwnGenerator = $this->makeGenerator($owner, ['operating_schedule' => '24h']);
        [, , $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'schedule' => '24h',
            'service_start_time' => null,
            'service_end_time' => null,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/owner/subscriptions/{$subscription->id}/transfer", [
                'generator_id' => $otherOwnGenerator->id,
            ]);

        $response->assertOk();
        $this->assertSame($otherOwnGenerator->id, $subscription->fresh()->generator_id);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $subscription->id,
            'description' => 'owner_transferred_subscription',
        ]);
    }

    public function test_owner_cannot_transfer_subscription_onto_another_owners_generator(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $otherOwner = $this->makeOwner();
        $foreignGenerator = $this->makeGenerator($otherOwner, ['operating_schedule' => '24h']);
        [, , $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'schedule' => '24h',
            'service_start_time' => null,
            'service_end_time' => null,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        // Both source and target belong to the acting owner's authority check
        // (transferByOwn passes, since the SOURCE generator is theirs); the
        // TARGET-generator ownership guard lives inside TransferSubscriptionAction
        // (locked, transactional) and raises a 422 ValidationException — not 403 —
        // because it is a business-rule check on the supplied value, not a
        // resource-authorization check. Nothing changes either way.
        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/owner/subscriptions/{$subscription->id}/transfer", [
                'generator_id' => $foreignGenerator->id,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('generator_id');

        $this->assertSame($generator->id, $subscription->fresh()->generator_id);
        $this->assertDatabaseMissing('activity_log', [
            'subject_id' => $subscription->id,
            'description' => 'owner_transferred_subscription',
        ]);
    }

    public function test_owner_cannot_transfer_another_owners_subscription(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $foreignGenerator = $this->makeGenerator($otherOwner);
        [, , $meter] = $this->makeSubscriberUser();

        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $foreignGenerator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $targetGenerator = $this->makeGenerator($owner, ['operating_schedule' => '24h']);

        // The acting owner doesn't own the subscription's CURRENT (source) generator
        // at all — this is a real authorization failure, correctly 403 via
        // SubscriptionPolicy::transferByOwn.
        $this->actingAs($owner)
            ->patchJson("/api/v1/owner/subscriptions/{$subscription->id}/transfer", [
                'generator_id' => $targetGenerator->id,
            ])
            ->assertStatus(403);

        $this->assertSame($foreignGenerator->id, $subscription->fresh()->generator_id);
    }

    /* ---------------------------------------------------------------
     | 3) Bulk Payment Reminder (Owner-scoped)
     |---------------------------------------------------------------*/

    public function test_owner_bulk_reminder_only_reaches_invoices_for_subscribers_on_own_generators(): void
    {
        Event::fake([InvoiceDueSoon::class]);

        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);

        [$ownSubscriberUser, , $ownMeter] = $this->makeSubscriberUser();
        $ownSubscription = Subscription::factory()->create([
            'subscriber_meter_id' => $ownMeter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $ownInvoice = Invoice::factory()->create([
            'subscription_id' => $ownSubscription->id,
            'status' => 'pending',
        ]);

        // Arbitrary subscriber not related to this owner at all.
        [$arbitrarySubscriberUser] = $this->makeSubscriberUser();

        // Subscriber that belongs to a DIFFERENT owner's generator.
        $otherOwner = $this->makeOwner();
        $otherGenerator = $this->makeGenerator($otherOwner);
        [$otherSubscriberUser, , $otherMeter] = $this->makeSubscriberUser();
        $otherSubscription = Subscription::factory()->create([
            'subscriber_meter_id' => $otherMeter->id,
            'generator_id' => $otherGenerator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        Invoice::factory()->create([
            'subscription_id' => $otherSubscription->id,
            'status' => 'overdue',
        ]);

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/owner/subscribers/bulk-payment-reminder', [
                'subscriber_ids' => [
                    $ownSubscriberUser->id,
                    $arbitrarySubscriberUser->id,
                    $otherSubscriberUser->id,
                ],
            ]);

        $response->assertOk();
        $this->assertSame(1, $response->json('data.reminded_invoices_count'));

        Event::assertDispatched(InvoiceDueSoon::class, function (InvoiceDueSoon $event) use ($ownInvoice) {
            return $event->invoice->id === $ownInvoice->id;
        });
        Event::assertDispatchedTimes(InvoiceDueSoon::class, 1);
    }

    public function test_subscriber_cannot_use_owner_bulk_payment_reminder_endpoint(): void
    {
        [$user] = $this->makeSubscriberUser();

        $this->actingAs($user)
            ->postJson('/api/v1/owner/subscribers/bulk-payment-reminder', [
                'subscriber_ids' => [$user->id],
            ])
            ->assertStatus(403);
    }
}
