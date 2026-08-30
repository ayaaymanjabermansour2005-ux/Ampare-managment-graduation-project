<?php

namespace Tests\Feature\Invoice;

use App\Enums\Role as RoleEnum;
use App\Events\InvoiceDueSoon;
use App\Exports\InvoicesExport;
use App\Listeners\SendInvoiceDueSoonNotification;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Subscriber;
use App\Models\SubscriberMeter;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\InvoiceDueSoonNotification;
use App\Services\InvoiceService;
use App\Services\Pdf\InvoicePdfService;
use App\Services\QrCodeService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

    /**
     * @return array{0: User, 1: Invoice}
     */
    private function makeInvoiceFor(User $owner, array $overrides = []): array
    {
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $subscriberUser = User::factory()->create();
        $subscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        $subscriber = Subscriber::factory()->create(['user_id' => $subscriberUser->id]);
        $meter = SubscriberMeter::factory()->create(['subscriber_id' => $subscriber->id]);
        $subscription = Subscription::factory()->create([
            'subscriber_meter_id' => $meter->id,
            'generator_id' => $generator->id,
            'billing_cycle' => 'monthly',
        ]);

        $invoice = Invoice::factory()->create(array_merge([
            'subscription_id' => $subscription->id,
        ], $overrides));

        return [$subscriberUser, $invoice];
    }

    private function patchWithIdempotency(User $actingAs, string $url, array $payload = [])
    {
        return $this->actingAs($actingAs)
            ->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->patchJson($url, $payload);
    }

    private function postWithIdempotency(User $actingAs, string $url, array $payload = [])
    {
        return $this->actingAs($actingAs)
            ->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson($url, $payload);
    }

    public function test_owner_can_view_own_invoice(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);

        $this->actingAs($owner)
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertOk();
    }

    public function test_owner_cannot_view_unrelated_invoice(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($otherOwner);

        $this->actingAs($owner)
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertStatus(403);
    }

    public function test_subscriber_can_view_own_invoice(): void
    {
        $owner = $this->makeOwner();
        [$subscriberUser, $invoice] = $this->makeInvoiceFor($owner);

        $this->actingAs($subscriberUser)
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertOk();
    }

    public function test_subscriber_cannot_view_unrelated_invoice(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);

        $otherSubscriberUser = User::factory()->create();
        $otherSubscriberUser->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $otherSubscriberUser->id]);

        $this->actingAs($otherSubscriberUser)
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_view_any_invoice(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);

        $this->actingAs($admin)
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertOk();
    }

    public function test_index_scoped_to_owner_generators(): void
    {
        $owner = $this->makeOwner();
        [, $ownInvoice] = $this->makeInvoiceFor($owner);

        $otherOwner = $this->makeOwner();
        $this->makeInvoiceFor($otherOwner);

        $response = $this->actingAs($owner)
            ->getJson('/api/v1/invoices');

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($ownInvoice->id));
        $this->assertCount(1, $ids);
    }

    public function test_owner_can_view_available_payment_methods_for_invoice(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);

        $this->actingAs($owner)
            ->getJson("/api/v1/invoices/{$invoice->id}/payment-methods")
            ->assertOk();
    }

    public function test_admin_can_cancel_pending_invoice(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner, ['status' => 'pending']);

        $this->actingAs($admin)
            ->patchJson("/api/v1/invoices/{$invoice->id}/cancel")
            ->assertOk();

        $this->assertSame('cancelled', $invoice->fresh()->status->value);
    }

    public function test_owner_cannot_cancel_invoice(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner, ['status' => 'pending']);

        $this->actingAs($owner)
            ->patchJson("/api/v1/invoices/{$invoice->id}/cancel")
            ->assertStatus(403);
    }

    public function test_cannot_cancel_already_paid_invoice(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner, ['status' => 'paid']);

        $this->actingAs($admin)
            ->patchJson("/api/v1/invoices/{$invoice->id}/cancel")
            ->assertStatus(422);

        $this->assertSame('paid', $invoice->fresh()->status->value);
    }

    public function test_owner_can_download_invoice_pdf(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);

        $response = $this->actingAs($owner)
            ->get("/api/v1/invoices/{$invoice->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /* ---------------------------------------------------------------
     | Bilingual invoice PDF (Arabic shaping preserved + real English)
     |---------------------------------------------------------------*/

    public function test_arabic_invoice_pdf_still_renders_correctly(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);

        app()->setLocale('ar');
        $service = app(InvoicePdfService::class);
        $response = $service->stream($invoice->fresh());

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_english_invoice_pdf_renders_with_translated_labels_and_ltr(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);
        $invoice->loadMissing(['subscription.generator.owner', 'subscription.subscriberMeter.subscriber.user', 'payments', 'commission', 'appliedOffer']);

        app()->setLocale('en');
        $invoiceService = app(InvoiceService::class);
        $qrService = app(QrCodeService::class);

        $html = view('pdf.invoice', [
            'invoice' => $invoice,
            'remainingBalance' => $invoiceService->remainingBalance($invoice),
            'verificationQr' => $qrService->invoiceVerificationQrBase64($invoice),
        ])->render();

        $this->assertStringContainsString('<html lang="en" dir="ltr">', $html);
        $this->assertStringContainsString('Invoice #', $html);
        $this->assertStringContainsString('Base amount', $html);
        $this->assertStringContainsString('Due date', $html);

        // بند H: صراحةً — ما في أي نص عربي متبقٍّ ثابت بالقالب (مش جزء من
        // بيانات فعلية زي أسماء المستخدمين، هاي محايدة اللغة أصلًا).
        $this->assertStringNotContainsString('فاتورة', $html);
        $this->assertStringNotContainsString('المبلغ', $html);
        $this->assertStringNotContainsString('تاريخ الاستحقاق', $html);
    }

    public function test_english_invoice_pdf_downloads_successfully_end_to_end(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);

        app()->setLocale('en');
        $service = app(InvoicePdfService::class);
        $response = $service->stream($invoice->fresh());

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_invoice_pdf_route_honors_lang_query_param_for_locale(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);

        $response = $this->actingAs($owner)
            ->get("/api/v1/invoices/{$invoice->id}/pdf?lang=en");

        $response->assertOk();
        $this->assertSame('en', app()->getLocale());
    }

    public function test_owner_cannot_download_unrelated_invoice_pdf(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($otherOwner);

        $this->actingAs($owner)
            ->get("/api/v1/invoices/{$invoice->id}/pdf")
            ->assertStatus(403);
    }

    public function test_owner_can_get_invoice_qr_code(): void
    {
        $owner = $this->makeOwner();
        [, $invoice] = $this->makeInvoiceFor($owner);

        $response = $this->actingAs($owner)
            ->getJson("/api/v1/invoices/{$invoice->id}/qr");

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.qr'));
    }

    public function test_export_only_includes_owners_own_invoices(): void
    {
        Excel::fake();

        $owner = $this->makeOwner();
        [, $ownInvoice] = $this->makeInvoiceFor($owner);

        $otherOwner = $this->makeOwner();
        $this->makeInvoiceFor($otherOwner);

        $this->actingAs($owner)
            ->get('/api/v1/invoices/export')
            ->assertOk();

        Excel::assertDownloaded(
            'invoices-'.now()->format('Y-m-d').'.xlsx',
            function (InvoicesExport $export) use ($ownInvoice) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->id === $ownInvoice->id;
            }
        );
    }

    public function test_subscriber_export_only_includes_own_invoices(): void
    {
        Excel::fake();

        $owner = $this->makeOwner();
        [$subscriberUser, $ownInvoice] = $this->makeInvoiceFor($owner);
        $this->makeInvoiceFor($owner);

        $this->actingAs($subscriberUser)
            ->get('/api/v1/invoices/export')
            ->assertOk();

        Excel::assertDownloaded(
            'invoices-'.now()->format('Y-m-d').'.xlsx',
            function (InvoicesExport $export) use ($ownInvoice) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->id === $ownInvoice->id;
            }
        );
    }

    public function test_unauthenticated_user_cannot_access_invoices(): void
    {
        $this->getJson('/api/v1/invoices')->assertStatus(401);
    }

    public function test_correcting_ils_invoice_keeps_final_amount_ils_unmultiplied(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        [, $invoice] = $this->makeInvoiceFor($owner, [
            'currency' => 'ILS',
            'exchange_rate' => null,
            'amount' => 100,
            'final_amount' => 100,
            'final_amount_ils' => 100,
            'status' => 'pending',
        ]);

        $response = $this->patchWithIdempotency($admin, "/api/v1/invoices/{$invoice->id}/correct", [
            'final_amount' => 150,
            'reason' => 'تصحيح خطأ بقراءة العداد.',
        ]);

        $response->assertOk();
        $invoice->refresh();
        $this->assertSame(150.0, (float) $invoice->final_amount);
        $this->assertSame(150.0, (float) $invoice->final_amount_ils);
    }

    public function test_correcting_usd_invoice_applies_exchange_rate(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        [, $invoice] = $this->makeInvoiceFor($owner, [
            'currency' => 'USD',
            'exchange_rate' => 3.70,
            'amount' => 100,
            'final_amount' => 100,
            'final_amount_ils' => 370,
            'status' => 'pending',
        ]);

        $response = $this->patchWithIdempotency($admin, "/api/v1/invoices/{$invoice->id}/correct", [
            'final_amount' => 200,
            'reason' => 'تصحيح خطأ بقراءة العداد.',
        ]);

        $response->assertOk();

        $invoice->refresh();
        $this->assertSame(200.0, (float) $invoice->final_amount);
        $this->assertSame(740.0, (float) $invoice->final_amount_ils);
    }

    public function test_cannot_correct_invoice_with_accepted_payments(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        [, $invoice] = $this->makeInvoiceFor($owner, ['status' => 'partially_paid']);

        $this->patchWithIdempotency($admin, "/api/v1/invoices/{$invoice->id}/correct", [
            'final_amount' => 200,
            'reason' => 'محاولة تصحيح غير صالحة.',
        ])->assertStatus(422);
    }

    public function test_technician_with_manually_granted_invoices_permission_sees_no_invoices(): void
    {
        $owner = $this->makeOwner();
        $this->makeInvoiceFor($owner);

        $technician = User::factory()->create();
        $technician->assignRole(RoleEnum::TECHNICIAN->value);
        $technician->givePermissionTo('invoices.view');

        $response = $this->actingAs($technician)->getJson('/api/v1/invoices');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.data'));
    }

    public function test_admin_can_reissue_cancelled_invoice(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        [, $invoice] = $this->makeInvoiceFor($owner, [
            'status' => 'cancelled',
            'final_amount' => 120,
            'final_amount_ils' => 120,
        ]);

        $response = $this->postWithIdempotency($admin, "/api/v1/invoices/{$invoice->id}/reissue");

        $response->assertStatus(201);
        $newInvoiceId = $response->json('data.id');

        $this->assertNotSame($invoice->id, $newInvoiceId);
        $this->assertDatabaseHas('invoices', [
            'id' => $newInvoiceId,
            'status' => 'pending',
            'final_amount' => 120,
            'subscription_id' => $invoice->subscription_id,
        ]);
    }

    public function test_cannot_reissue_non_cancelled_invoice(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        [, $invoice] = $this->makeInvoiceFor($owner, ['status' => 'pending']);

        $this->postWithIdempotency($admin, "/api/v1/invoices/{$invoice->id}/reissue")
            ->assertStatus(422);
    }

    public function test_owner_cannot_reissue_invoice(): void
    {
        $owner = $this->makeOwner();

        [, $invoice] = $this->makeInvoiceFor($owner, ['status' => 'cancelled']);

        $this->postWithIdempotency($owner, "/api/v1/invoices/{$invoice->id}/reissue")
            ->assertStatus(403);
    }

    public function test_command_dispatches_event_for_invoices_due_soon(): void
    {
        Event::fake([InvoiceDueSoon::class]);

        $subscription = Subscription::factory()->create()->load('subscriberMeter.subscriber.user');

        Invoice::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan('invoices:remind-due-soon', ['--days' => 3])
            ->assertSuccessful();

        Event::assertDispatched(InvoiceDueSoon::class);
    }

    public function test_listener_sends_notification_to_subscriber(): void
    {
        Notification::fake();

        $subscription = Subscription::factory()->create()->load('subscriberMeter.subscriber.user');
        $subscriber = $subscription->subscriberMeter->subscriber->user;

        $invoice = Invoice::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'pending',
        ]);
        $invoice->load('subscription.subscriberMeter.subscriber.user');

        (new SendInvoiceDueSoonNotification)->handle(new InvoiceDueSoon($invoice));

        Notification::assertSentTo($subscriber, InvoiceDueSoonNotification::class);
    }
}
