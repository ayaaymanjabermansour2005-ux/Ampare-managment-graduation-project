<?php

namespace Tests\Feature\Auth;

use App\Models\Neighborhood;
use App\Models\OwnerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'أحمد علي',
            'email' => 'ahmad.owner@example.com',
            'phone' => '+970599123456',
            'notes' => 'ملاحظة تجريبية',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',

            'generator_name' => 'مولد الحي الشرقي',
            'generator_price_per_kw' => '2.5',
            'generator_currency' => 'ILS',
            'generator_capacity_kw' => '50',
            'generator_city' => 'غزة',
            'generator_address' => 'شارع الرئيسي',
        ], $overrides);
    }

    private function validDocuments(): array
    {
        return [
            'id_document' => UploadedFile::fake()->create('id.pdf', 500, 'application/pdf'),
            'business_license' => UploadedFile::fake()->create('license.pdf', 500, 'application/pdf'),
            'generator_photo' => UploadedFile::fake()->image('generator.jpg'),
            'ownership_contract' => UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf'),
        ];
    }

    public function test_submitting_a_complete_application_succeeds_and_creates_record_with_all_generator_fields(): void
    {
        Storage::fake('attachments');

        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(),
            $this->validDocuments()
        ));

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('owner_applications', [
            'email' => 'ahmad.owner@example.com',
            'generator_name' => 'مولد الحي الشرقي',
            'generator_city' => 'غزة',
            'generator_capacity_kw' => 50,
        ]);

        $application = OwnerApplication::where('email', 'ahmad.owner@example.com')->firstOrFail();
        $this->assertEquals(2.5, (float) $application->generator_price_per_kw);

        $this->assertCount(4, $application->fresh('attachments')->attachments);
    }

    public function test_missing_generator_name_is_rejected_with_clear_validation_error_not_a_500(): void
    {
        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(['generator_name' => '']),
            $this->validDocuments()
        ));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['generator_name']);
    }

    public function test_missing_generator_price_is_rejected_with_clear_validation_error_not_a_500(): void
    {
        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(['generator_price_per_kw' => '']),
            $this->validDocuments()
        ));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['generator_price_per_kw']);
    }

    public function test_missing_generator_city_is_rejected_with_clear_validation_error_not_a_500(): void
    {
        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(['generator_city' => '']),
            $this->validDocuments()
        ));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['generator_city']);
    }

    public function test_each_missing_required_document_is_rejected_individually(): void
    {
        foreach (['id_document', 'business_license', 'generator_photo', 'ownership_contract'] as $missingField) {
            $documents = $this->validDocuments();
            unset($documents[$missingField]);

            $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
                $this->validPayload(['email' => "test-{$missingField}@example.com"]),
                $documents
            ));

            $response->assertStatus(422)
                ->assertJsonValidationErrors([$missingField]);
        }
    }

    public function test_document_with_disallowed_mime_type_is_rejected(): void
    {
        $documents = $this->validDocuments();
        $documents['id_document'] = UploadedFile::fake()->create('malware.exe', 500, 'application/x-msdownload');

        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(),
            $documents
        ));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_document']);
    }

    public function test_document_exceeding_max_size_is_rejected(): void
    {
        $maxKb = config('attachments.max_size_kb');

        $documents = $this->validDocuments();
        $documents['id_document'] = UploadedFile::fake()->create('too_big.pdf', $maxKb + 100, 'application/pdf');

        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(),
            $documents
        ));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_document']);
    }

    public function test_optional_neighborhood_id_is_accepted_when_valid(): void
    {
        $neighborhood = Neighborhood::factory()->create();

        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(['generator_neighborhood_id' => $neighborhood->id]),
            $this->validDocuments()
        ));

        $response->assertStatus(201);
        $this->assertDatabaseHas('owner_applications', [
            'email' => 'ahmad.owner@example.com',
            'generator_neighborhood_id' => $neighborhood->id,
        ]);
    }

    public function test_invalid_neighborhood_id_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(['generator_neighborhood_id' => 999999]),
            $this->validDocuments()
        ));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['generator_neighborhood_id']);
    }

    public function test_duplicate_pending_email_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(),
            $this->validDocuments()
        ))->assertStatus(201);

        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(),
            $this->validDocuments()
        ));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_already_registered_as_user_is_rejected(): void
    {
        $existingUser = User::factory()->create(['email' => 'ahmad.owner@example.com']);

        $response = $this->postJson('/api/v1/auth/owner-applications', array_merge(
            $this->validPayload(['email' => $existingUser->email]),
            $this->validDocuments()
        ));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
