<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        $admin = User::firstOrCreate(
            ['email' => 'admin@ampare.test'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('Password123!'),
            ]
        );
        $admin->forceFill([
            'status' => 'active',
            'email_verified_at' => now(),
        ])->save();

        if (! $admin->hasRole(RoleEnum::ADMIN->value)) {
            $admin->assignRole(RoleEnum::ADMIN->value);
        }
    }
}
