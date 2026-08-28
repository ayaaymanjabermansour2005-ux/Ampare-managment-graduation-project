<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // الأدوار نفسها بيانات مرجعية/بنيوية مطلوبة على كل بيئة (بما فيها production)
        // ليعمل نظام الصلاحيات — لا علاقة لها بمشكلة الحساب المولَّد أدناه.
        foreach (RoleEnum::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'sanctum',
            ]);
        }

        $this->seedLocalAdmin();
    }

    /**
     * SEC-005: هذا الـ Seeder كان يُنشئ حساب أدمن بكلمة مرور ثابتة ومتوقّعة
     * ('Password123!') بدون أي حارس بيئة — لو اتشغّل هذا الـ Seeder فعليًا على
     * production (خطأ شائع بسكربتات النشر)، بينتج حساب أدمن حقيقي بكلمة مرور
     * منشورة أصلًا بالكود المصدري. لا يُنشئ هذا الحساب على الإطلاق على production؛
     * وعلى البيئات الأخرى (local/testing) تُقرأ بيانات الدخول من config/seeding.php
     * (بدورها env('DEV_ADMIN_EMAIL')/env('DEV_ADMIN_PASSWORD'))، وإذا لم تُحدَّد
     * كلمة مرور يتم توليد واحدة عشوائية قوية بدل استخدام قيمة ثابتة بالكود.
     */
    private function seedLocalAdmin(): void
    {
        if (app()->environment('production')) {
            // متعمّد: تزويد أول حساب أدمن على production يجب أن يتم بعملية آمنة
            // منفصلة تمامًا عن الكود المصدري (مثال: أمر artisan تفاعلي أو إدخال
            // مباشر بقاعدة البيانات من قِبل من يملك صلاحية الوصول)، وليس عبر Seeder.
            return;
        }

        $email = config('seeding.dev_admin_email');
        $password = config('seeding.dev_admin_password');
        $wasGenerated = false;

        if (! $password) {
            $password = Str::password(16);
            $wasGenerated = true;
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'System Admin',
                'password' => Hash::make($password),
            ]
        );

        $wasCreated = $admin->wasRecentlyCreated;

        $admin->forceFill([
            'status' => 'active',
            'email_verified_at' => now(),
        ])->save();

        if (! $admin->hasRole(RoleEnum::ADMIN->value)) {
            $admin->assignRole(RoleEnum::ADMIN->value);
        }

        // لا تُطبع/تُسجَّل كلمة المرور إلا لما تكون فعلًا كلمة المرور الحقيقية
        // للحساب المُنشأ للتو (لا معنى لطباعتها لو الحساب كان موجود مسبقًا بكلمة
        // مرور مختلفة)، وفقط بالـ console المحلي — أبدًا بملفات الـ log.
        if ($wasCreated && $wasGenerated && app()->environment('local') && $this->command) {
            $this->command->warn("RoleSeeder: generated local admin password for {$email}: {$password}");
        }
    }
}
