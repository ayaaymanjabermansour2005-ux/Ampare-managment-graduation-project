<?php

namespace Tests\Feature\Notification;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\FaultReportedAdminNotification;
use App\Notifications\FaultReportedNotification;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\NewComplaintNotification;
use App\Services\InvoiceService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regression coverage for the admin notification bell showing nothing for
 * new complaints, new faults, and overdue invoices: none of these events
 * ever notified an admin user before (FaultReported only notified the
 * generator's owner, and there was no "complaint submitted" / "invoice
 * overdue" event at all) — the bell was correctly empty because nothing
 * was ever created for the admin, not because of a frontend bug.
 */
class AdminEventNotificationTest extends TestCase
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

    /**
     * @return array{0: Subscriber, 1: User, 2: Subscription}
     */
    private function makeConnectedSubscriber(Generator $generator): array
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $user->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
        ]);

        return [$subscriber, $user, $subscription];
    }

    public function test_admin_is_notified_when_a_new_complaint_is_submitted(): void
    {
        Notification::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/complaints', [
                'subject' => 'شكوى عامة',
                'description' => 'تفاصيل الشكوى العامة.',
            ])
            ->assertStatus(201);

        Notification::assertSentTo($admin, NewComplaintNotification::class);
    }

    public function test_admin_and_owner_are_both_notified_when_a_new_fault_is_reported(): void
    {
        Notification::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, $subscriberUser] = $this->makeConnectedSubscriber($generator);

        $this->actingAs($subscriberUser)->postJson('/api/v1/faults', [
            'generator_id' => $generator->id,
            'title' => 'المولد متوقف',
            'description' => 'المولد توقف فجأة بدون إنذار.',
            'priority' => 'high',
        ])->assertStatus(201);

        Notification::assertSentTo($admin, FaultReportedAdminNotification::class);
        Notification::assertSentTo($owner, FaultReportedNotification::class);
    }

    public function test_admin_is_notified_when_an_invoice_becomes_overdue(): void
    {
        Notification::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        [, , $subscription] = $this->makeConnectedSubscriber($generator);

        $invoice = Invoice::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'due_date' => now()->subDays(10),
        ]);

        $count = app(InvoiceService::class)->markOverdueInvoices();

        $this->assertSame(1, $count);
        $this->assertSame('overdue', $invoice->fresh()->status->value);
        Notification::assertSentTo($admin, InvoiceOverdueNotification::class);
    }
}
