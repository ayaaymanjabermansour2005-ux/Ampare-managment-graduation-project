<?php

namespace Tests\Feature\Invoice;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InvoiceVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_signed_link_returns_invoice_verification_result(): void
    {
        $invoice = Invoice::factory()->create();

        $url = URL::temporarySignedRoute('invoices.verify', now()->addMinutes(60), ['invoice' => $invoice->id]);

        $response = $this->getJson($url);

        $response->assertOk();
        $this->assertSame($invoice->id, $response->json('data.invoice_id'));
        $this->assertSame($invoice->status->value, $response->json('data.status'));
    }

    public function test_response_only_exposes_whitelisted_fields(): void
    {
        $invoice = Invoice::factory()->create();

        $url = URL::temporarySignedRoute('invoices.verify', now()->addMinutes(60), ['invoice' => $invoice->id]);

        $response = $this->getJson($url);

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            ['invoice_id', 'status', 'status_label', 'final_amount', 'due_date', 'generator_name', 'issued_at'],
            array_keys($response->json('data'))
        );
    }

    public function test_unsigned_request_is_rejected(): void
    {
        $invoice = Invoice::factory()->create();

        $this->getJson("/api/invoices/{$invoice->id}/verify")->assertStatus(403);
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $invoice = Invoice::factory()->create();

        $url = URL::temporarySignedRoute('invoices.verify', now()->addMinutes(60), ['invoice' => $invoice->id]);
        $tamperedUrl = $url.'&tampered=1';

        $this->getJson($tamperedUrl)->assertStatus(403);
    }

    public function test_expired_signature_is_rejected(): void
    {
        $invoice = Invoice::factory()->create();

        $url = URL::temporarySignedRoute('invoices.verify', now()->subMinutes(5), ['invoice' => $invoice->id]);

        $this->getJson($url)->assertStatus(403);
    }

    public function test_nonexistent_invoice_returns_404(): void
    {
        $url = URL::temporarySignedRoute('invoices.verify', now()->addMinutes(60), ['invoice' => 999999]);

        $this->getJson($url)->assertStatus(404);
    }
}
