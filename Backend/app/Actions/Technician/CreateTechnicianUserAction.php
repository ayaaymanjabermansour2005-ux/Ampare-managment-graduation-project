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
    /**
     * ARCH-001: كانت هذه الدالة مكرَّرة بنسختين شبه متطابقتين
     * (CreateTechnicianUserAction / AdminCreateTechnicianForOwnerAction) —
     * الفرق الوحيد الفعلي بينهما هو مين الفاعل (المالك نفسه، أو أدمن نيابةً
     * عنه) لغرض سجل الـ activity log. `$onBehalfOfAdmin` يوحّد الحالتين.
     */
    public function execute(CreateTechnicianUserData $data, User $owner, ?User $onBehalfOfAdmin = null): Technician
    {
        if (! $owner->isOwner()) {
            // مفتاح الرسالة owner_id (لا owner) — يطابق حقل owner_id الفعلي
            // بطلب المسار الإداري (AdminCreateTechnicianRequest)، وهو
            // المسار الوحيد الذي يختبر هذا الشرط فعليًا؛ في مسار المالك
            // لنفسه (createAccount) هذا الشرط دفاعي بحت (الـ route/policy
            // يضمنان مسبقًا أن $owner هو المستخدم الحالي المالك).
            throw ValidationException::withMessages([
                'owner_id' => ['المستخدم المحدَّد ليس مالك مولد.'],
            ]);
        }

        return DB::transaction(function () use ($data, $owner, $onBehalfOfAdmin) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $user->assignRole(Role::TECHNICIAN->value);

            $technician = Technician::create([
                'user_id' => $user->id,
                'owner_id' => $owner->id,
                'status' => TechnicianStatus::Active,
                'notes' => $data->notes,
            ]);

            $activity = activity()->performedOn($technician);

            if ($onBehalfOfAdmin) {
                // مسجَّل بوضوح كإجراء دعم فني نيابةً عن المالك — يميّزه عن
                // الحالة الطبيعية حيث المالك ينشئ حساب الفني بنفسه مباشرة.
                $activity->causedBy($onBehalfOfAdmin)
                    ->withProperties(['on_behalf_of_owner_id' => $owner->id, 'on_behalf_of_owner_name' => $owner->name])
                    ->log('admin_created_technician_on_behalf_of_owner');
            } else {
                $activity->causedBy($owner)->log('owner_created_technician');
            }

            $fresh = $technician->fresh('user');

            if (! $fresh instanceof Technician) {
                throw new \RuntimeException('Failed to reload the technician immediately after creation.');
            }

            return $fresh;
        });
    }
}
