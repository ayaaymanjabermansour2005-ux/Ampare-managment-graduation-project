<?php

namespace Tests\Feature\Technician;

use App\Enums\Role as RoleEnum;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\Technician;
use App\Models\TechnicianTask;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class TechnicianTest extends TestCase
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

    private function makeTechnicianRoleUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::TECHNICIAN->value);

        return $user;
    }

    private function makeSubscriberUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUBSCRIBER->value);
        Subscriber::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_owner_can_create_private_technician(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/technicians', ['user_id' => $technicianUser->id]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('technicians', [
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
            'status' => 'active',
        ]);
    }

    public function test_cannot_create_technician_for_user_without_technician_role(): void
    {
        $owner = $this->makeOwner();
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($owner)
            ->postJson('/api/v1/technicians', ['user_id' => $subscriberUser->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_cannot_create_duplicate_technician_profile(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson('/api/v1/technicians', ['user_id' => $technicianUser->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    /**
     * FIX (منطق أعمال): يستبدل test_admin_can_create_platform_technician
     * و test_admin_creating_without_type_defaults_to_platform المحذوفين —
     * "فني المنصة" أُلغي كليًا، والأدمن ممنوع من إنشاء أي فني الآن، الإنشاء
     * حصري لمالك المولد. هذا الاختبار يحمي القرار الجديد صراحة.
     */
    public function test_admin_cannot_create_technician(): void
    {
        $admin = $this->makeAdmin();
        $technicianUser = $this->makeTechnicianRoleUser();

        $this->actingAs($admin)
            ->postJson('/api/v1/technicians', ['user_id' => $technicianUser->id])
            ->assertStatus(403);
    }

    public function test_subscriber_cannot_create_technician(): void
    {
        $subscriberUser = $this->makeSubscriberUser();
        $technicianUser = $this->makeTechnicianRoleUser();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/technicians', ['user_id' => $technicianUser->id])
            ->assertStatus(403);
    }

    public function test_owner_can_view_own_private_technician(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->getJson("/api/v1/technicians/{$technician->id}")
            ->assertOk();
    }

    public function test_owner_cannot_view_another_owners_private_technician(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $otherOwner->id]);

        $this->actingAs($owner)
            ->getJson("/api/v1/technicians/{$technician->id}")
            ->assertStatus(403);
    }

    public function test_technician_can_view_own_record(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);

        $this->actingAs($technicianUser)
            ->getJson("/api/v1/technicians/{$technician->id}")
            ->assertOk();
    }

    public function test_owner_can_update_own_private_technician_status(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technicians/{$technician->id}", ['status' => 'suspended'])
            ->assertOk();

        $this->assertSame('suspended', $technician->fresh()->status->value);
    }

    public function test_owner_cannot_update_another_owners_technician(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $otherOwner->id]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/technicians/{$technician->id}", ['status' => 'suspended'])
            ->assertStatus(403);
    }

    /**
     * FIX: يستبدل test_admin_can_update_platform_technician — يتحقق الآن
     * من صلاحية الإشراف العامة للأدمن على أي فني (خاص بمالك)، مش على
     * فني منصة (أُلغي).
     */
    public function test_admin_can_update_any_technician(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/technicians/{$technician->id}", ['status' => 'inactive'])
            ->assertOk();
    }

    public function test_owner_can_delete_private_technician_without_active_tasks(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/technicians/{$technician->id}")
            ->assertOk();
    }

    public function test_cannot_delete_technician_with_active_tasks(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        TechnicianTask::factory()->create([
            'generator_id' => $generator->id,
            'technician_id' => $technician->id,
            'requested_by' => $owner->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/technicians/{$technician->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('technician');
    }

    /**
     * FIX: يستبدل test_available_returns_linked_private_technicians_when_present
     * و test_available_falls_back_to_platform_when_no_private_linked — الفني
     * المرتبط صراحة بمولّد معيّن يظهر لهذا المولّد فقط وليس لمولّد آخر لنفس
     * المالك، بدون أي مفهوم "منصة" احتياطي.
     */
    public function test_available_returns_only_explicitly_linked_technician(): void
    {
        $owner = $this->makeOwner();
        $linkedGenerator = Generator::factory()->create(['owner_id' => $owner->id]);
        $otherGenerator = Generator::factory()->create(['owner_id' => $owner->id]);

        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
            'status' => 'active',
        ]);
        $technician->generators()->attach($linkedGenerator->id);

        $response = $this->actingAs($owner)
            ->getJson("/api/v1/generators/{$linkedGenerator->id}/available-technicians");

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($technician->id));

        $response = $this->actingAs($owner)
            ->getJson("/api/v1/generators/{$otherGenerator->id}/available-technicians");

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($technician->id));
    }

    public function test_owner_can_create_technician_account(): void
    {
        $owner = $this->makeOwner();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/technicians/create-account', [
                'name' => 'أحمد الفني',
                'email' => 'ahmad.tech@example.com',
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ]);

        $response->assertStatus(201);

        $newUser = User::where('email', 'ahmad.tech@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole(RoleEnum::TECHNICIAN->value));

        $this->assertDatabaseHas('technicians', [
            'user_id' => $newUser->id,
            'owner_id' => $owner->id,
        ]);
    }

    /**
     * FIX: يستبدل test_admin_creating_account_results_in_platform_technician
     * — الأدمن ممنوع كليًا من إنشاء حساب فني الآن.
     */
    public function test_admin_cannot_create_technician_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/technicians/create-account', [
                'name' => 'فني',
                'email' => 'admin.created.tech@example.com',
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ])
            ->assertStatus(403);
    }

    public function test_create_account_requires_name_email_and_password(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->postJson('/api/v1/technicians/create-account', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_create_account_rejects_duplicate_email(): void
    {
        $owner = $this->makeOwner();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($owner)
            ->postJson('/api/v1/technicians/create-account', [
                'name' => 'فني',
                'email' => 'taken@example.com',
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_subscriber_cannot_create_technician_account(): void
    {
        $subscriberUser = $this->makeSubscriberUser();

        $this->actingAs($subscriberUser)
            ->postJson('/api/v1/technicians/create-account', [
                'name' => 'فني',
                'email' => 'x@example.com',
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ])
            ->assertStatus(403);
    }

    public function test_owner_can_link_private_technician_to_own_generator(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/api/v1/technicians/{$technician->id}/generators/{$generator->id}")
            ->assertOk();

        $this->assertDatabaseHas('generator_technician', [
            'technician_id' => $technician->id,
            'generator_id' => $generator->id,
        ]);
    }

    public function test_owner_can_unlink_technician_from_generator(): void
    {
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);
        $technician->generators()->attach($generator->id);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/technicians/{$technician->id}/generators/{$generator->id}")
            ->assertOk();

        $this->assertDatabaseMissing('generator_technician', [
            'technician_id' => $technician->id,
            'generator_id' => $generator->id,
        ]);
    }

    public function test_owner_cannot_link_another_owners_technician(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $otherOwner->id]);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/api/v1/technicians/{$technician->id}/generators/{$generator->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_link_any_technician_to_any_generator(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create(['user_id' => $technicianUser->id, 'owner_id' => $owner->id]);
        $generator = Generator::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($admin)
            ->postJson("/api/v1/technicians/{$technician->id}/generators/{$generator->id}")
            ->assertOk();
    }

    public function test_available_includes_unlinked_private_technician_for_any_owner_generator(): void
    {
        $owner = $this->makeOwner();
        $generatorA = Generator::factory()->create(['owner_id' => $owner->id]);
        $generatorB = Generator::factory()->create(['owner_id' => $owner->id]);

        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
            'status' => 'active',
        ]);

        foreach ([$generatorA, $generatorB] as $generator) {
            $response = $this->actingAs($owner)
                ->getJson("/api/v1/generators/{$generator->id}/available-technicians");

            $response->assertOk();
            $ids = collect($response->json('data'))->pluck('id');
            $this->assertTrue(
                $ids->contains($technician->id),
                "الفني بلا روابط لازم يظهر لمولّد {$generator->id} (Fallback)."
            );
        }
    }

    public function test_available_excludes_unlinked_private_technician_once_linked_elsewhere_only(): void
    {
        $owner = $this->makeOwner();
        $linkedGenerator = Generator::factory()->create(['owner_id' => $owner->id]);
        $otherGenerator = Generator::factory()->create(['owner_id' => $owner->id]);

        $technicianUser = $this->makeTechnicianRoleUser();
        $technician = Technician::factory()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => $owner->id,
            'status' => 'active',
        ]);
        $technician->generators()->attach($linkedGenerator->id);

        $response = $this->actingAs($owner)
            ->getJson("/api/v1/generators/{$otherGenerator->id}/available-technicians");

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($technician->id));
    }

    public function test_unauthenticated_user_cannot_access_technicians(): void
    {
        $this->getJson('/api/v1/technicians')->assertStatus(401);
    }

    /**
     * تغطية جديدة لمسار "الأدمن ينشئ فنيًا خاصًا نيابةً عن مالك مولد بناءً على
     * طلبه" (POST users/technicians) — لا يوجد اختبار سابق لهذا المسار رغم وجوده
     * في الكود. الأدمن هنا actor فقط، الملكية تبقى للمالك المحدَّد.
     */
    public function test_admin_can_create_technician_on_behalf_of_owner(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $response = $this->actingAs($admin)->postJson('/api/v1/users/technicians', [
            'owner_id' => $owner->id,
            'name' => 'فني نيابة عن مالك',
            'email' => 'proxy.tech@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertStatus(201);

        $newUser = User::where('email', 'proxy.tech@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole(RoleEnum::TECHNICIAN->value));

        $this->assertDatabaseHas('technicians', [
            'user_id' => $newUser->id,
            'owner_id' => $owner->id,
        ]);
    }

    public function test_admin_create_technician_on_behalf_of_owner_requires_owner_role(): void
    {
        $admin = $this->makeAdmin();
        $notAnOwner = $this->makeSubscriberUser();

        $response = $this->actingAs($admin)->postJson('/api/v1/users/technicians', [
            'owner_id' => $notAnOwner->id,
            'name' => 'فني',
            'email' => 'invalid.owner.tech@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('owner_id');
    }

    public function test_owner_cannot_create_technician_on_behalf_of_owner_via_admin_route(): void
    {
        $owner = $this->makeOwner();
        $anotherOwner = $this->makeOwner();

        $response = $this->actingAs($owner)->postJson('/api/v1/users/technicians', [
            'owner_id' => $anotherOwner->id,
            'name' => 'فني',
            'email' => 'owner.route.tech@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_create_technician_on_behalf_of_owner_logs_activity(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();

        $this->actingAs($admin)->postJson('/api/v1/users/technicians', [
            'owner_id' => $owner->id,
            'name' => 'فني موثّق بالسجل',
            'email' => 'logged.tech@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertStatus(201);

        $activity = Activity::query()
            ->where('description', 'admin_created_technician_on_behalf_of_owner')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($admin->id, $activity->causer_id);
        $this->assertSame($owner->id, $activity->getExtraProperty('on_behalf_of_owner_id'));
    }

    /**
     * تثبيت صريح على أن الفني لا يمكن أن يوجد بلا مالك (لا وجود لـ "فني منصة")
     * على أعمق مستوى ممكن: قيد NOT NULL على مستوى قاعدة البيانات ذاتها.
     */
    public function test_technician_owner_id_is_never_nullable(): void
    {
        $technicianUser = $this->makeTechnicianRoleUser();

        $this->expectException(QueryException::class);

        Technician::query()->create([
            'user_id' => $technicianUser->id,
            'owner_id' => null,
        ]);
    }

    public function test_admin_can_export_all_technicians(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        Technician::factory()->create(['user_id' => $this->makeTechnicianRoleUser()->id, 'owner_id' => $owner->id]);
        Technician::factory()->create(['user_id' => $this->makeTechnicianRoleUser()->id, 'owner_id' => $otherOwner->id]);

        $this->actingAs($admin)->get('/api/v1/technicians/export')->assertOk();

        \Maatwebsite\Excel\Facades\Excel::assertDownloaded(
            'technicians-'.now()->format('Y-m-d').'.xlsx',
            fn (\App\Exports\TechniciansExport $export) => $export->query()->count() === 2
        );
    }

    public function test_owner_export_is_scoped_to_own_technicians_only(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();

        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $ownTechnician = Technician::factory()->create(['user_id' => $this->makeTechnicianRoleUser()->id, 'owner_id' => $owner->id]);
        Technician::factory()->create(['user_id' => $this->makeTechnicianRoleUser()->id, 'owner_id' => $otherOwner->id]);

        $this->actingAs($owner)->get('/api/v1/technicians/export')->assertOk();

        \Maatwebsite\Excel\Facades\Excel::assertDownloaded(
            'technicians-'.now()->format('Y-m-d').'.xlsx',
            function (\App\Exports\TechniciansExport $export) use ($ownTechnician) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->id === $ownTechnician->id;
            }
        );
    }

    public function test_subscriber_cannot_export_technicians(): void
    {
        $subscriber = $this->makeSubscriberUser();

        $this->actingAs($subscriber)
            ->getJson('/api/v1/technicians/export')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_export_technicians(): void
    {
        $this->getJson('/api/v1/technicians/export')->assertStatus(401);
    }
}
