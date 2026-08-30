<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethodType;
use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\MeterReading;
use App\Models\Neighborhood;
use App\Models\PaymentMethod;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguarded(fn () => $this->seed());
    }

    private function seed(): void
    {
        $admin = User::where('email', 'admin@ampare.test')->first();

        // ==================== حساب مالك المولد التجريبي ====================
        $demoOwner = User::updateOrCreate(
            ['email' => 'demo-owner@ampare.test'],
            [
                'name' => 'مالك تجريبي',
                'password' => Hash::make(str()->random(40)),
                'email_verified_at' => now(),
                'status' => 'active',
                'is_guest_demo' => true,
            ]
        );
        if (! $demoOwner->hasRole(RoleEnum::GENERATOR_OWNER->value)) {
            $demoOwner->assignRole(RoleEnum::GENERATOR_OWNER->value);
        }

        // ==================== حساب المشترك التجريبي ====================
        $demoSubscriberUser = User::updateOrCreate(
            ['email' => 'demo-subscriber@ampare.test'],
            [
                'name' => 'مشترك تجريبي',
                'password' => Hash::make(str()->random(40)),
                'email_verified_at' => now(),
                'status' => 'active',
                'is_guest_demo' => true,
            ]
        );
        if (! $demoSubscriberUser->hasRole(RoleEnum::SUBSCRIBER->value)) {
            $demoSubscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        }

        // ==================== الحي والموقع ====================
        $neighborhood = Neighborhood::firstOrCreate(['name' => 'حي تجريبي']);
        $location = Location::firstOrCreate(
            ['city' => 'غزة', 'neighborhood_id' => $neighborhood->id, 'address' => 'موقع تجريبي للعرض'],
            ['latitude' => 31.5017, 'longitude' => 34.4668]
        );

        // ==================== مولد المالك التجريبي ====================
        $demoGenerator = Generator::firstOrCreate(
            ['owner_id' => $demoOwner->id, 'name' => 'المولد التجريبي'],
            [
                'price_per_kw' => 1.25,
                'currency' => 'ILS',
                'capacity_kw' => 75,
                'location_id' => $location->id,
                'status' => 'active',
                'operating_schedule' => '24h',
            ]
        );

        // طريقة دفع للمالك التجريبي (عشان يبين شكل الفواتير كامل)
        PaymentMethod::firstOrCreate(
            ['user_id' => $demoOwner->id, 'type' => PaymentMethodType::Wallet],
            ['is_default' => true, 'currency' => 'ILS']
        );

        // ==================== ملف المشترك التجريبي ====================
        $demoSubscriber = Subscriber::firstOrCreate(
            ['user_id' => $demoSubscriberUser->id],
            ['neighborhood_id' => $neighborhood->id, 'address' => 'شارع تجريبي', 'joined_at' => now()->subDays(20)]
        );

        $demoMeter = SubscriberMeter::firstOrCreate(
            ['meter_number' => 'DEMO-0001'],
            ['subscriber_id' => $demoSubscriber->id, 'property_label' => 'منزل تجريبي', 'status' => 'active']
        );

        // ==================== الاشتراك الرابط بينهم ====================
        $demoSubscription = Subscription::firstOrCreate(
            ['subscriber_meter_id' => $demoMeter->id, 'generator_id' => $demoGenerator->id, 'schedule' => '24h'],
            [
                'agreed_price_per_kw' => $demoGenerator->price_per_kw,
                'currency' => $demoGenerator->currency,
                'requested_capacity_kw' => 15,
                'contract_type' => 'residential',
                'start_date' => now()->subDays(20),
                'status' => 'active',
            ]
        );

        // ==================== قراءة عداد وفاتورة (عشان تبين بيانات مالية حقيقية) ====================
        $reading = MeterReading::firstOrCreate(
            ['subscription_id' => $demoSubscription->id, 'reading_date' => now()->subDays(2)->toDateString()],
            ['previous_reading' => 500, 'current_reading' => 545, 'created_by' => $admin?->id ?? $demoOwner->id]
        );

        Invoice::firstOrCreate(
            ['subscription_id' => $demoSubscription->id, 'due_date' => now()->addDays(5)->toDateString()],
            [
                'meter_reading_id' => $reading->id,
                'amount' => 56.25,
                'discount_amount' => 0,
                'discount_id' => null,
                'final_amount' => 56.25,
                'currency' => 'ILS',
                'exchange_rate' => null,
                'final_amount_ils' => 56.25,
                'status' => InvoiceStatus::Pending,
            ]
        );
    }
}
