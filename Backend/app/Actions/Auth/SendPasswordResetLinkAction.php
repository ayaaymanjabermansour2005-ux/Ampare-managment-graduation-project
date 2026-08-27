<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction
{
    public function execute(string $email): void
    {
        Password::broker('users')->sendResetLink(['email' => $email]);
    }
}
