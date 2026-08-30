<?php

namespace Tests\Feature\Offer;

use App\Enums\Role as RoleEnum;
use App\Exports\OffersExport;
use App\Models\Generator;
use App\Models\Offer;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use App\Services\OfferService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class OfferTest extends TestCase
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

    private function makeGenerator(User $owner): Generator
    {
        return Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);
    }

    /**
     * @return array{0: Subscriber, 1: User, 2: Subscription}
     */
    private function makeConnectedSubscriber(Generator $generator, string $beneficiaryType = 'normal'): array
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create([
            'user_id' => $user->id,
            'beneficiary_type' => $beneficiaryType,
        ]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        return [$subscriber, $user, $subscription];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'خصم الصيف',
            'description' => 'خصم موسمي.',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'target_mode' => 'all',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
        ], $overrides);
    }

    public function test_owner_can_create_all_mode_offer(): void
    {
        $owner = $this->makeOwner();
        $this->makeGenerator($owner);

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/offers', $this->validPayload());

        $response->assertStatus(201);
        $this->assertSame('all', $response->json('data.target_mode'));
        $this->assertNull($response->json('data.beneficiary_type'));
    }

    public function test_beneficiary_type_required_when_target_mode_is_beneficiary(): void
    {
        $owner = $this->makeOwner();
        $this->makeGenerator($owner);

        $this->actingAs($owner)
            ->postJson('/api/v1/offers', $this->validPayload(['target_mode' => 'beneficiary']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('beneficiary_type');
    }

    public function test_beneficiary_type_is_dropped_when_target_mode_is_all(): void
    {
        $owner = $this->makeOwner();
        $this->makeGenerator($owner);

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/offers', $this->validPayload([
                'target_mode' => 'all',
                'beneficiary_type' => 'special',
            ]));

        $response->assertStatus(201);
        $this->assertNull($response->json('data.beneficiary_type'));
    }

    public function test_subscriber_ids_required_when_target_mode_is_selected(): void
    {
        $owner = $this->makeOwner();
        $this->makeGenerator($owner);

        $this->actingAs($owner)
            ->postJson('/api/v1/offers', $this->validPayload(['target_mode' => 'selected']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscriber_ids');
    }

    public function test_subscriber_ids_must_belong_to_owner(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        $otherOwner = $this->makeOwner();
        $otherGenerator = $this->makeGenerator($otherOwner);
        [$foreignSubscriber] = $this->makeConnectedSubscriber($otherGenerator);

        $this->actingAs($owner)
            ->postJson('/api/v1/offers', $this->validPayload([
                'target_mode' => 'selected',
                'subscriber_ids' => [$foreignSubscriber->id],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscriber_ids');
    }

    public function test_percentage_discount_over_100_is_rejected(): void
    {
        $owner = $this->makeOwner();
        $this->makeGenerator($owner);

        $this->actingAs($owner)
            ->postJson('/api/v1/offers', $this->validPayload([
                'discount_type' => 'percentage',
                'discount_value' => 150,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount_value');
    }

    public function test_owner_without_active_generator_cannot_create_offer(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/offers', $this->validPayload())
            ->assertStatus(403);
    }

    public function test_all_mode_offer_visible_to_any_connected_subscriber(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator, 'normal');

        $offer = Offer::create([
            'owner_id' => $owner->id,
            'title' => 'عرض عام',
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'target_mode' => 'all',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/offers/{$offer->id}")
            ->assertOk();
    }

    public function test_beneficiary_mode_offer_visible_only_to_matching_type(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [, $specialUser] = $this->makeConnectedSubscriber($generator, 'special');
        [, $normalUser] = $this->makeConnectedSubscriber($generator, 'normal');

        $offer = Offer::create([
            'owner_id' => $owner->id,
            'title' => 'عرض المستفيدين',
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'target_mode' => 'beneficiary',
            'beneficiary_type' => 'special',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->actingAs($specialUser)
            ->getJson("/api/v1/offers/{$offer->id}")
            ->assertOk();

        $this->actingAs($normalUser)
            ->getJson("/api/v1/offers/{$offer->id}")
            ->assertStatus(403);
    }

    public function test_selected_mode_offer_visible_only_to_chosen_subscribers(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [$chosenSubscriber, $chosenUser] = $this->makeConnectedSubscriber($generator);
        [, $otherUser] = $this->makeConnectedSubscriber($generator);

        $offer = Offer::create([
            'owner_id' => $owner->id,
            'title' => 'عرض خاص',
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'target_mode' => 'selected',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);
        $offer->targetedSubscribers()->attach($chosenSubscriber->id);

        $this->actingAs($chosenUser)
            ->getJson("/api/v1/offers/{$offer->id}")
            ->assertOk();

        $this->actingAs($otherUser)
            ->getJson("/api/v1/offers/{$offer->id}")
            ->assertStatus(403);
    }

    public function test_owner_cannot_view_another_owners_offer(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $this->makeGenerator($otherOwner);

        $offer = Offer::create([
            'owner_id' => $otherOwner->id,
            'title' => 'عرض تاني',
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'target_mode' => 'all',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/offers/{$offer->id}")
            ->assertStatus(403);
    }

    public function test_resolve_best_offer_prioritizes_selected_over_beneficiary_over_all(): void
    {
        $owner = $this->makeOwner();
        $generator = $this->makeGenerator($owner);
        [$subscriber,, $subscription] = $this->makeConnectedSubscriber($generator, 'special');

        Offer::create([
            'owner_id' => $owner->id,
            'title' => 'عام',
            'discount_type' => 'fixed',
            'discount_value' => 100,
            'target_mode' => 'all',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
            'status' => 'active',
        ]);

        Offer::create([
            'owner_id' => $owner->id,
            'title' => 'فئة',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'target_mode' => 'beneficiary',
            'beneficiary_type' => 'special',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
            'status' => 'active',
        ]);

        $selectedOffer = Offer::create([
            'owner_id' => $owner->id,
            'title' => 'خاص',
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'target_mode' => 'selected',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
            'status' => 'active',
        ]);
        $selectedOffer->targetedSubscribers()->attach($subscriber->id);

        $result = app(OfferService::class)->resolveBestOffer($subscription->fresh(['generator']), 1000);

        $this->assertNotNull($result);
        $this->assertSame($selectedOffer->id, $result['offer']->id);
        $this->assertSame(10.0, $result['discount_amount']);
    }

    public function test_owner_can_cancel_own_offer(): void
    {
        $owner = $this->makeOwner();
        $this->makeGenerator($owner);

        $offer = Offer::create([
            'owner_id' => $owner->id,
            'title' => 'عرض',
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'target_mode' => 'all',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/offers/{$offer->id}/cancel")
            ->assertOk();

        $this->assertSame('cancelled', $offer->fresh()->status->value);
    }

    public function test_unauthenticated_user_cannot_access_offers(): void
    {
        $this->getJson('/api/v1/offers')->assertStatus(401);
    }

    /* ---------------------------------------------------------------
     | Excel export
     |---------------------------------------------------------------*/

    public function test_owner_can_export_own_offers(): void
    {
        Excel::fake();

        $owner = $this->makeOwner();
        Offer::create([
            'owner_id' => $owner->id, 'title' => 'عرضي الأول', 'discount_type' => 'fixed',
            'discount_value' => 10, 'target_mode' => 'all', 'start_date' => now(), 'end_date' => now()->addDays(5),
            'status' => 'active',
        ]);

        $this->actingAs($owner)->get('/api/v1/offers/export')->assertOk();

        Excel::assertDownloaded(
            'offers-'.now()->format('Y-m-d').'.xlsx',
            fn (OffersExport $export) => $export->query()->count() === 1
        );
    }

    public function test_offer_export_is_scoped_to_own_owner_only(): void
    {
        Excel::fake();

        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        Offer::create([
            'owner_id' => $owner->id, 'title' => 'عرضي', 'discount_type' => 'fixed',
            'discount_value' => 10, 'target_mode' => 'all', 'start_date' => now(), 'end_date' => now()->addDays(5),
            'status' => 'active',
        ]);
        Offer::create([
            'owner_id' => $otherOwner->id, 'title' => 'عرض غيري', 'discount_type' => 'fixed',
            'discount_value' => 10, 'target_mode' => 'all', 'start_date' => now(), 'end_date' => now()->addDays(5),
            'status' => 'active',
        ]);

        $this->actingAs($owner)->get('/api/v1/offers/export')->assertOk();

        Excel::assertDownloaded(
            'offers-'.now()->format('Y-m-d').'.xlsx',
            function (OffersExport $export) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->title === 'عرضي';
            }
        );
    }

    public function test_unauthenticated_user_cannot_export_offers(): void
    {
        $this->getJson('/api/v1/offers/export')->assertStatus(401);
    }
}
