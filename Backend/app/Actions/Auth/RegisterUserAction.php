<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\RegisterUserData;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterUserAction
{
    /**
     * @param  bool  $autoVerify  
     */
    public function execute(RegisterUserData $data, bool $autoVerify = false): User
    {
        if (! in_array(Setting::get('allow_subscriber_registration', '1'), ['1', 1, true, 'true'], true)) {
            throw ValidationException::withMessages([
                'email' => ['تسجيل حسابات جديدة معطَّل مؤقتًا من قِبل إدارة المنصة.'],
            ]);
        }

        $requireEmailVerification = in_array(
            Setting::get('require_email_verification', '1'),
            ['1', 1, true, 'true'],
            true
        );

        $requiresReview = in_array(
            Setting::get('review_new_accounts_before_activation', '0'),
            ['1', 1, true, 'true'],
            true
        );

        return DB::transaction(function () use ($data, $autoVerify, $requireEmailVerification, $requiresReview) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'phone' => $data->phone,
            ]);

            $shouldAutoVerify = $autoVerify || ! $requireEmailVerification;

            if ($shouldAutoVerify) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            if ($requiresReview) {
                $user->forceFill(['status' => UserStatus::PendingReview])->save();
            }

            $user->assignRole(Role::SUBSCRIBER->value);

            Subscriber::create([
                'user_id' => $user->id,
                'neighborhood_id' => $data->neighborhoodId,
                'address' => $data->address,
                'joined_at' => now(),
            ]);

            if (! $shouldAutoVerify) {
                $user->sendEmailVerificationNotification();
            }

            return $user;
        });
    }
}
