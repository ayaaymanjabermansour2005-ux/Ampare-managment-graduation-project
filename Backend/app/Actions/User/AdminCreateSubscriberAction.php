<?php

namespace App\Actions\User;

use App\DTOs\User\AdminCreateSubscriberData;
use App\Enums\Role;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminCreateSubscriberAction
{
    public function execute(AdminCreateSubscriberData $data, User $admin): User
    {
        return DB::transaction(function () use ($data, $admin) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'password' => Hash::make($data->password),
            ]);

            // ينشئه الأدمن مباشرة فيُعتبر موثَّقًا فورًا — لا حاجة لتفعيل بريد
            // أو مراجعة إضافية، بعكس التسجيل الذاتي عبر الموقع.
            $user->forceFill(['email_verified_at' => now()])->save();

            $user->assignRole(Role::SUBSCRIBER->value);

            Subscriber::create([
                'user_id' => $user->id,
                'neighborhood_id' => $data->neighborhoodId,
                'address' => $data->address,
                'joined_at' => now(),
            ]);

            activity()
                ->causedBy($admin)
                ->performedOn($user)
                ->log('subscriber_created_by_admin');

            return $user->refresh();
        });
    }
}
