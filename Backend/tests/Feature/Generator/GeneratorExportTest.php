<?php

namespace Tests\Feature\Generator;

use App\Enums\Role as RoleEnum;
use App\Exports\GeneratorsExport;
use App\Models\FuelReading;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * تدقيق شامل — الجولة الخامسة: GeneratorController::export() كان يتجاهل كل
 * فلاتر الجدول (city/owner_id/capacity/fuel/subscribers/revenue) عدا
 * search/status، فالملف المُصدَّر كان يحتوي بيانات أوسع مما يظهر فعليًا
 * بالشاشة المفلترة. هاي الاختبارات تتحقق أن كل فلتر ينعكس فعليًا على الاستعلام.
 */
class GeneratorExportTest extends TestCase
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

    public function test_admin_can_export_all_generators_without_filters(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        Generator::factory()->create();
        Generator::factory()->create();

        $this->actingAs($admin)->get('/api/v1/generators/export')->assertOk();

        Excel::assertDownloaded(
            'generators-'.now()->format('Y-m-d').'.xlsx',
            fn (GeneratorsExport $export) => $export->query()->count() === 2
        );
    }

    public function test_export_respects_owner_id_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $ownerA = $this->makeOwner();
        $ownerB = $this->makeOwner();
        $matching = Generator::factory()->create(['owner_id' => $ownerA->id]);
        Generator::factory()->create(['owner_id' => $ownerB->id]);

        $this->actingAs($admin)
            ->get('/api/v1/generators/export?owner_id='.$ownerA->id)
            ->assertOk();

        Excel::assertDownloaded(
            'generators-'.now()->format('Y-m-d').'.xlsx',
            function (GeneratorsExport $export) use ($matching) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->id === $matching->id;
            }
        );
    }

    public function test_owner_export_ignores_owner_id_param_and_stays_scoped_to_self(): void
    {
        Excel::fake();

        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $own = Generator::factory()->create(['owner_id' => $owner->id]);
        Generator::factory()->create(['owner_id' => $otherOwner->id]);

        // حتى لو مرّر owner_id لمالك تاني، السكوب يبقى مقفول على مولداته هو فقط.
        $this->actingAs($owner)
            ->get('/api/v1/generators/export?owner_id='.$otherOwner->id)
            ->assertOk();

        Excel::assertDownloaded(
            'generators-'.now()->format('Y-m-d').'.xlsx',
            function (GeneratorsExport $export) use ($own) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->id === $own->id;
            }
        );
    }

    public function test_export_respects_city_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $ramallahLocation = Location::factory()->create(['city' => 'رام الله']);
        $nablusLocation = Location::factory()->create(['city' => 'نابلس']);
        $matching = Generator::factory()->create(['location_id' => $ramallahLocation->id]);
        Generator::factory()->create(['location_id' => $nablusLocation->id]);

        $this->actingAs($admin)
            ->get('/api/v1/generators/export?city='.urlencode('رام الله'))
            ->assertOk();

        Excel::assertDownloaded(
            'generators-'.now()->format('Y-m-d').'.xlsx',
            function (GeneratorsExport $export) use ($matching) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->id === $matching->id;
            }
        );
    }

    public function test_export_respects_capacity_range_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $small = Generator::factory()->create(['capacity_kw' => 10]);
        $mid = Generator::factory()->create(['capacity_kw' => 50]);
        $large = Generator::factory()->create(['capacity_kw' => 200]);

        $this->actingAs($admin)
            ->get('/api/v1/generators/export?capacity_min=20&capacity_max=100')
            ->assertOk();

        Excel::assertDownloaded(
            'generators-'.now()->format('Y-m-d').'.xlsx',
            function (GeneratorsExport $export) use ($mid, $small, $large) {
                $ids = $export->query()->get()->pluck('id');

                return $ids->contains($mid->id) && ! $ids->contains($small->id) && ! $ids->contains($large->id);
            }
        );
    }

    public function test_export_respects_fuel_percentage_range_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();

        $low = Generator::factory()->create(['tank_capacity_liters' => 100]);
        FuelReading::factory()->create(['generator_id' => $low->id, 'tank_level_liters' => 10, 'reading_date' => now()->toDateString()]);

        $high = Generator::factory()->create(['tank_capacity_liters' => 100]);
        FuelReading::factory()->create(['generator_id' => $high->id, 'tank_level_liters' => 90, 'reading_date' => now()->toDateString()]);

        $this->actingAs($admin)
            ->get('/api/v1/generators/export?fuel_min=50')
            ->assertOk();

        Excel::assertDownloaded(
            'generators-'.now()->format('Y-m-d').'.xlsx',
            function (GeneratorsExport $export) use ($high, $low) {
                $ids = $export->query()->get()->pluck('id');

                return $ids->contains($high->id) && ! $ids->contains($low->id);
            }
        );
    }

    public function test_export_respects_subscribers_range_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $withSubscriber = Generator::factory()->create();
        Subscription::factory()->create(['generator_id' => $withSubscriber->id, 'status' => 'active']);
        $withoutSubscriber = Generator::factory()->create();

        $this->actingAs($admin)
            ->get('/api/v1/generators/export?subscribers_min=1')
            ->assertOk();

        Excel::assertDownloaded(
            'generators-'.now()->format('Y-m-d').'.xlsx',
            function (GeneratorsExport $export) use ($withSubscriber, $withoutSubscriber) {
                $ids = $export->query()->get()->pluck('id');

                return $ids->contains($withSubscriber->id) && ! $ids->contains($withoutSubscriber->id);
            }
        );
    }

    public function test_export_respects_revenue_range_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();

        $earning = Generator::factory()->create();
        $subscription = Subscription::factory()->create(['generator_id' => $earning->id]);
        Invoice::factory()->create(['subscription_id' => $subscription->id, 'status' => 'paid', 'final_amount_ils' => 300]);

        $notEarning = Generator::factory()->create();

        $this->actingAs($admin)
            ->get('/api/v1/generators/export?revenue_min=100')
            ->assertOk();

        Excel::assertDownloaded(
            'generators-'.now()->format('Y-m-d').'.xlsx',
            function (GeneratorsExport $export) use ($earning, $notEarning) {
                $ids = $export->query()->get()->pluck('id');

                return $ids->contains($earning->id) && ! $ids->contains($notEarning->id);
            }
        );
    }
}
