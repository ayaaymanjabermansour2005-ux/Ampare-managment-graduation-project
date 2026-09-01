<?php

namespace Tests\Feature\Seeders;

use App\Models\Generator;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the admin "Generator Owners" and "Generators" tables
 * showing blank commission/fuel-type columns: PlatformUsersSeeder never set
 * commission_mode/commission_rate on seeded owner users, and no generator
 * seeder/factory varied fuel_type, so every seeded row fell back to the same
 * (or null) value.
 */
class PlatformUsersSeederCommissionAndFuelTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_owners_have_commission_data_and_generators_have_varied_fuel_types(): void
    {
        Model::unguarded(fn () => (new DatabaseSeeder)->run());

        $owners = User::whereIn('email', [
            'mahmoud.abushamala@example.test',
            'sanaa.alagha@example.test',
            'khaled.alnajjar@example.test',
        ])->get();

        $this->assertCount(3, $owners);
        foreach ($owners as $owner) {
            $this->assertNotNull($owner->commission_mode, "owner {$owner->email} missing commission_mode");
        }

        $fixedModeOwner = $owners->firstWhere('email', 'mahmoud.abushamala@example.test');
        $this->assertSame('fixed', $fixedModeOwner->commission_mode->value);
        $this->assertNotNull($fixedModeOwner->commission_rate);

        $tieredModeOwner = $owners->firstWhere('email', 'sanaa.alagha@example.test');
        $this->assertSame('tiered', $tieredModeOwner->commission_mode->value);
        $this->assertNull($tieredModeOwner->commission_rate);

        $fuelTypes = Generator::query()->pluck('fuel_type')->unique();
        $this->assertGreaterThan(1, $fuelTypes->count(), 'expected seeded generators to have more than one distinct fuel_type');
    }
}
