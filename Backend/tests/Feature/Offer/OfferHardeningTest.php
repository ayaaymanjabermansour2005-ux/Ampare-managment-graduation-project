<?php

namespace Tests\Feature\Offer;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Offer;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    private function makeOwnerWithActiveGenerator(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        Generator::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

        return $owner;
    }

    public function test_updating_percentage_offer_above_100_is_rejected(): void
    {
        $owner = $this->makeOwnerWithActiveGenerator();

        $offer = Offer::create([
            'owner_id' => $owner->id,
            'title' => 'خصم تشغيل تجريبي',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'target_mode' => 'all',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/offers/{$offer->id}", ['discount_value' => 250])
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount_value');

        $this->assertEquals(20, $offer->fresh()->discount_value);
    }

    public function test_updating_percentage_offer_within_100_succeeds(): void
    {
        $owner = $this->makeOwnerWithActiveGenerator();

        $offer = Offer::create([
            'owner_id' => $owner->id,
            'title' => 'خصم تشغيل تجريبي',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'target_mode' => 'all',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/offers/{$offer->id}", ['discount_value' => 45])
            ->assertOk();

        $this->assertEquals(45, $offer->fresh()->discount_value);
    }

    public function test_updating_fixed_offer_above_100_is_allowed(): void
    {
        $owner = $this->makeOwnerWithActiveGenerator();

        $offer = Offer::create([
            'owner_id' => $owner->id,
            'title' => 'خصم مبلغ ثابت',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'target_mode' => 'all',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/offers/{$offer->id}", ['discount_value' => 250])
            ->assertOk();

        $this->assertEquals(250, $offer->fresh()->discount_value);
    }
}
