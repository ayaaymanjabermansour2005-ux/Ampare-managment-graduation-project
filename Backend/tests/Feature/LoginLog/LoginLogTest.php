<?php

namespace Tests\Feature\LoginLog;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LoginLogTest extends TestCase
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

    /**
     * بيانات واقعية حقيقية: نستدعي endpoint تسجيل الدخول الفعلي (نجاح
     * ومحاولة فاشلة) بدل إنشاء سجلات Activity يدويًا — هذا هو المصدر
     * الحقيقي الوحيد لسجلات login_succeeded/login_failed (راجع
     * LoginUserAction::execute()).
     */
    private function generateRealLoginLogs(): User
    {
        $user = User::factory()->create(['password' => bcrypt('CorrectPass123!')]);
        $user->assignRole(RoleEnum::SUBSCRIBER->value);

        $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'CorrectPass123!',
        ])->assertOk();

        $this->postJson('/api/v1/auth/logout')->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'WrongPassword',
        ])->assertStatus(422);

        return $user;
    }

    public function test_admin_can_export_login_logs(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $this->generateRealLoginLogs();

        $this->actingAs($admin)->get('/api/v1/admin/login-logs/export')->assertOk();

        Excel::assertDownloaded(
            'login-logs-'.now()->format('Y-m-d').'.xlsx',
            fn (\App\Exports\LoginLogsExport $export) => $export->query()->count() === 2
        );
    }

    public function test_login_log_export_respects_event_filter(): void
    {
        Excel::fake();

        $admin = $this->makeAdmin();
        $this->generateRealLoginLogs();

        $this->actingAs($admin)
            ->get('/api/v1/admin/login-logs/export?event=login_failed')
            ->assertOk();

        Excel::assertDownloaded(
            'login-logs-'.now()->format('Y-m-d').'.xlsx',
            function (\App\Exports\LoginLogsExport $export) {
                $rows = $export->query()->get();

                return $rows->count() === 1 && $rows->first()->description === 'login_failed';
            }
        );
    }

    public function test_non_admin_cannot_export_login_logs(): void
    {
        $subscriber = $this->generateRealLoginLogs();

        $this->actingAs($subscriber)
            ->getJson('/api/v1/admin/login-logs/export')
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_export_login_logs(): void
    {
        $this->getJson('/api/v1/admin/login-logs/export')->assertStatus(401);
    }
}
