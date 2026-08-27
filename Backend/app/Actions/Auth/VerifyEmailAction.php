<?php

namespace App\Actions\Auth;

use App\Models\User;

class VerifyEmailAction
{
    public function execute(int $id, string $hash): bool
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return false;
        }

        if ($user->hasVerifiedEmail()) {
            return true;
        }

        $user->markEmailAsVerified();

        return true;
    }
}
