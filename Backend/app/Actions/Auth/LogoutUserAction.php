<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;

class LogoutUserAction
{
    public function execute(): void
    {
        Auth::guard('web')->logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }
}
