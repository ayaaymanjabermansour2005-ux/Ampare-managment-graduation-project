<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\UnlockAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AccountLockoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 	  				إدارة المستخدمين وسجل التدقيق
 */
class UserLockoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AccountLockoutService $lockoutService
    ) {}

    public function lockedIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $locked = $this->lockoutService->lockedAccounts();

        return $this->success(
            message: 'قائمة الحسابات المقفولة حاليًا.',
            data: UserResource::collection($locked)
        );
    }

    public function unlock(Request $request, User $user, UnlockAccountAction $action): JsonResponse
    {
        $this->authorize('unlock', $user);

        $action->execute($user, $request->user());

        return $this->success(message: 'تم فك قفل الحساب بنجاح.');
    }
}
