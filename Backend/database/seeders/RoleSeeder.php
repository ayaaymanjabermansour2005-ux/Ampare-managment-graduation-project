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
        foreach (RoleEnum::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'sanctum',
            ]);
        }

        $this->seedLocalAdmin();
    }

    private function seedLocalAdmin(): void
    {
        if (app()->environment('production')) {
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

        if ($wasCreated && $wasGenerated && app()->environment('local') && $this->command) {
            $this->command->warn("RoleSeeder: generated local admin password for {$email}: {$password}");
        }
    }
}
