<?php

namespace App\Actions\User;

use App\DTOs\User\CreateGeneratorOwnerData;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateGeneratorOwnerAction
{
    public function execute(CreateGeneratorOwnerData $data, User $createdBy): User
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $owner = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'password' => Hash::make($data->password),
            ]);

            $owner->forceFill(['email_verified_at' => now()])->save();

            $owner->assignRole(Role::GENERATOR_OWNER->value);

            activity()
                ->causedBy($createdBy)
                ->performedOn($owner)
                ->log('admin_created_generator_owner');

            return $owner->fresh('roles');
        });
    }
}
