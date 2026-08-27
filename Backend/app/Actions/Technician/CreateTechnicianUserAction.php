<?php

namespace App\Actions\Technician;

use App\DTOs\Technician\CreateTechnicianUserData;
use App\Enums\Role;
use App\Enums\TechnicianStatus;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class CreateTechnicianUserAction
{
    public function execute(CreateTechnicianUserData $data, User $creator): Technician
    {
        if (! $creator->isOwner()) {
            throw ValidationException::withMessages([
                'owner' => ['فقط مالك المولد يمكنه إنشاء حساب فني جديد.'],
            ]);
        }

        return DB::transaction(function () use ($data, $creator) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $user->assignRole(Role::TECHNICIAN->value);

            $technician = Technician::create([
                'user_id' => $user->id,
                'owner_id' => $creator->id,
                'status' => TechnicianStatus::Active,
                'notes' => $data->notes,
            ]);

            activity()
                ->causedBy($creator)
                ->performedOn($technician)
                ->log('owner_created_technician');

            return $technician->fresh('user');
        });
    }
}
