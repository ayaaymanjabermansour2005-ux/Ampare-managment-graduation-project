<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\Auth\SessionInvalidationResult;
use App\Support\Auth\SessionInvalidator;

class LogoutOtherDevicesAction
{
    public function __construct(
        protected SessionInvalidator $sessionInvalidator
    ) {}

    public function execute(User $user): SessionInvalidationResult
    {
        return $this->sessionInvalidator->invalidateAllFor($user, keepCurrent: true);
    }
}
