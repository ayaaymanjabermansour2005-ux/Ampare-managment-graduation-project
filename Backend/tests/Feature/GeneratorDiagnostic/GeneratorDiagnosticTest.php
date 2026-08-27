<?php

namespace Tests\Feature\GeneratorDiagnostic;

use App\Contracts\AiChatProviderContract;
use App\Enums\Role as RoleEnum;
use App\Exceptions\AiProviderException;
use App\Models\FaultPrediction;
use App\Models\Generator;
use App\Models\GeneratorDiagnosticReading;
use App\Models\Subscriber;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GeneratorDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    private function bindAiProvider(\Closure $replyFactory, bool $throws = false): void
    {
        $mock = Mockery::mock(AiChatProviderContract::class);

        if ($throws) {
            $mock->shouldReceive('reply')->andThrow(new AiProviderException('service unavailable'));
        } else {
            $mock->shouldReceive('reply')->andReturnUsing($replyFactory);
        }

        $this->app->instance(AiChatProviderContract::class, $mock);
    }

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleEnum::GENERATOR_OWNER->value);

        return $owner;
    }

    private function makeSubscriberUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'operating_hours' => 1200.5,
            'temperature_celsius' => 85.5,
            'oil_level_percent' => 70,
            'load_percent' => 60,
            'voltage' => 220,
            'frequency_hz' => 50,
            'smoke_level' => 'none',
            'vibration_level' => 'normal',
            'reading_date' => now()->toDateString(),
        ], $overrides);
    }

    public function test_owner_can_record_reading(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->postJson("/api/v1/generators/{$generator->id}/diagnostics", $this->validPayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('generator_diagnostic_readings', [
            'generator_id' => $generator->id,
            'recorded_by' => $owner->id,
        ]);
    }

    public function test_linked_technician_can_record_reading(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
        ]);
        $technician->generators()->attach($generator->id);

        $this->actingAs($technicianUser)
            ->postJson("/api/v1/generators/{$generator->id}/diagnostics", $this->validPayload())
            ->assertStatus(201);
    }

    public function test_unrelated_technician_cannot_record_reading(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $otherOwner = $this->makeOwner();
        $technicianUser = User::factory()->create();
        $technicianUser->assignRole(RoleEnum::TECHNICIAN->value);
        Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $otherOwner->id,
        ]);

        $this->actingAs($technicianUser)
            ->postJson("/api/v1/generators/{$generator->id}/diagnostics", $this->validPayload())
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_record_reading(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/generators/{$generator->id}/diagnostics", $this->validPayload())
            ->assertStatus(403);
    }

    public function test_reading_date_cannot_be_in_future(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson(
                "/api/v1/generators/{$generator->id}/diagnostics",
                $this->validPayload(['reading_date' => now()->addDay()->toDateString()])
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('reading_date');
    }

    public function test_operating_hours_is_required(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $payload = $this->validPayload();
        unset($payload['operating_hours']);

        $this->actingAs($owner)
            ->postJson("/api/v1/generators/{$generator->id}/diagnostics", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('operating_hours');
    }

    public function test_owner_can_view_reading_history(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        GeneratorDiagnosticReading::create([
            ...$this->validPayload(),
            'generator_id' => $generator->id,
            'recorded_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->getJson("/api/v1/generators/{$generator->id}/diagnostics");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_analyze_creates_fault_prediction_with_parsed_json(): void
    {
        $this->bindAiProvider(fn() => json_encode([
            'risk_percentage' => 78,
            'predicted_fault_type' => 'ارتفاع حرارة المحرك',
            'recommendation' => 'افحصي نظام التبريد فورًا.',
        ], JSON_UNESCAPED_UNICODE));

        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $reading = GeneratorDiagnosticReading::create([
            ...$this->validPayload(),
            'generator_id' => $generator->id,
            'recorded_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->postJson("/api/v1/generator-diagnostics/{$reading->id}/analyze");

        $response->assertStatus(201);
        $this->assertDatabaseHas('fault_predictions', [
            'generator_diagnostic_reading_id' => $reading->id,
            'source' => 'sensor_analysis',
            'prediction_type' => 'ارتفاع حرارة المحرك',
            'confidence' => '78.00',
            'recommendation' => 'افحصي نظام التبريد فورًا.',
        ]);
    }

    public function test_analyze_falls_back_gracefully_on_invalid_json(): void
    {
        $this->bindAiProvider(fn() => 'هذا رد غير منظم مش JSON إطلاقًا.');

        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $reading = GeneratorDiagnosticReading::create([
            ...$this->validPayload(),
            'generator_id' => $generator->id,
            'recorded_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->postJson("/api/v1/generator-diagnostics/{$reading->id}/analyze");

        $response->assertStatus(201);
        $this->assertDatabaseHas('fault_predictions', [
            'generator_diagnostic_reading_id' => $reading->id,
            'source' => 'sensor_analysis',
            'confidence' => null,
        ]);
    }

    public function test_analyze_handles_provider_failure_gracefully(): void
    {
        $this->bindAiProvider(fn() => '', throws: true);

        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $reading = GeneratorDiagnosticReading::create([
            ...$this->validPayload(),
            'generator_id' => $generator->id,
            'recorded_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->postJson("/api/v1/generator-diagnostics/{$reading->id}/analyze")
            ->assertStatus(201);
    }

    public function test_cannot_analyze_same_reading_twice(): void
    {
        $this->bindAiProvider(fn() => json_encode([
            'risk_percentage' => 10,
            'predicted_fault_type' => null,
            'recommendation' => 'لا يوجد خطر واضح.',
        ]));

        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $reading = GeneratorDiagnosticReading::create([
            ...$this->validPayload(),
            'generator_id' => $generator->id,
            'recorded_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->postJson("/api/v1/generator-diagnostics/{$reading->id}/analyze")
            ->assertStatus(201);

        $this->actingAs($owner)
            ->postJson("/api/v1/generator-diagnostics/{$reading->id}/analyze")
            ->assertStatus(422);

        $this->assertSame(1, FaultPrediction::where('generator_diagnostic_reading_id', $reading->id)->count());
    }

    public function test_subscriber_cannot_trigger_analysis(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $reading = GeneratorDiagnosticReading::create([
            ...$this->validPayload(),
            'generator_id' => $generator->id,
            'recorded_by' => $owner->id,
        ]);

        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->postJson("/api/v1/generator-diagnostics/{$reading->id}/analyze")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_diagnostics(): void
    {
        $owner = $this->makeOwner();
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->getJson("/api/v1/generators/{$generator->id}/diagnostics")->assertStatus(401);
    }
}
