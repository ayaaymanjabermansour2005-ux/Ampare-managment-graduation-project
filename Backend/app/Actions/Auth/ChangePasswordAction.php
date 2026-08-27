<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\Auth\SessionInvalidationResult;
use App\Support\Auth\SessionInvalidator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ChangePasswordAction
{
    public function __construct(
        protected SessionInvalidator $sessionInvalidator
    ) {}

    public function execute(User $user, string $newPassword): SessionInvalidationResult
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
        ])->save();

        return $this->sessionInvalidator->invalidateAllFor($user, keepCurrent: true);
    }
}
