<?php

namespace App\Actions\Auth;

use App\Models\User;

class ResendVerificationEmailAction
{
    public function execute(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}
