<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\AccountLockoutService;

class UnlockAccountAction
{
    public function __construct(
        protected AccountLockoutService $lockoutService
    ) {}

    public function execute(User $user, User $admin): void
    {
        $this->lockoutService->unlock($user, $admin);
    }
}
